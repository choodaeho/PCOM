<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini API 래퍼 (무료 티어 최적화)
 *
 * ─ 현재 유효 무료 티어 모델 (2026년 6월 이후 기준) ───────────────────────
 *  gemini-2.5-flash      : RPM=5  / RPD=20   ← 기본 모델
 *  gemini-2.5-flash-lite : 별도 RPD 한도      ← fallback 경량 모델
 * ─────────────────────────────────────────────────────────────────────────
 * ⚠️ 지원 종료 모델 (2026년 6월 1일부로 사용 불가):
 *   - gemini-2.0-flash      → 완전 종료
 *   - gemini-2.0-flash-lite → 완전 종료
 *
 * 뉴스 컨텍스트 전략 (Google Search 그라운딩 대체):
 *   - KoreanNewsService가 RSS로 수집한 실제 뉴스를 프롬프트에 직접 주입
 *   - Gemini는 제공된 기사 중 참고한 번호(refs)를 JSON에 포함
 *   - 그라운딩 API 호출 없음 → 500 RPD 한도 미소진, HTTP 400 위험 없음
 *
 * ⚠️ 중요 설정 (절대 삭제 금지):
 *   - thinkingBudget=0  : thinking 토큰이 maxOutputTokens 예산 선점 방지
 *   - responseMimeType  : 사용 금지 (그라운딩 400 에러 + 조기 종료 유발)
 *   - useGrounding=false: 항상 false (Google Search 그라운딩 미사용)
 *
 * 무료 티어 RPD=20 제약:
 *   - posts_per_faction × 3진영 + 댓글 수 ≤ 20 이내로 설정 권장
 *
 * 429 Rate-limit 시 지수 백오프 재시도 (최대 3회)
 * 안전 필터 차단(SAFETY) / 저작권(RECITATION) 시 자동 재시도
 */
class GeminiService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const TIMEOUT  = 60;

    /**
     * 모델 우선순위 목록 (앞에서부터 시도 → 404/429 시 다음 모델로)
     *
     * ⚠️ gemini-2.0-flash / gemini-2.0-flash-lite → 2026년 6월 1일 지원 종료 (사용 금지)
     * ⚠️ gemini-1.5-flash 계열은 v1beta deprecated → 사용 금지
     *
     * 무료 tier RPD 한도: gemini-2.5-flash=20, gemini-2.5-flash-lite=별도
     * → 2.5-flash RPD 소진(429) 시 자동으로 2.5-flash-lite 로 전환됨
     */
    private const MODELS = [
        'gemini-2.5-flash',        // ✅ 현재 기본 모델 (무료: RPM=5 / RPD=20 / 그라운딩 500 RPD)
        'gemini-2.5-flash-lite',   // ✅ fallback 경량 모델 (무료: 별도 RPD 한도)
    ];

    /** 마지막 오류 사유 (Job에서 읽어 로그에 기록) */
    private string $lastErrorReason = '';

    public function __construct(private readonly string $apiKey)
    {
    }

    public function getLastErrorReason(): string
    {
        return $this->lastErrorReason;
    }

    // ─────────────────────────────────────────────────────────
    // Public: 게시글 생성
    // ─────────────────────────────────────────────────────────

    /**
     * AI 게시글 생성
     *
     * @param  string $faction      진영 (conservative|moderate|progressive)
     * @param  string $topic        주제 키워드
     * @param  string $boardType    게시판 유형 (azit|battle)
     * @param  bool   $useGrounding 미사용 (하위 호환성 유지, 항상 false 처리)
     * @param  list<array{title:string, summary:string, url:string, source:string}> $newsContext
     *         KoreanNewsService에서 수집한 RSS 뉴스 목록
     *         Gemini 프롬프트에 직접 주입 → Google Search 그라운딩 대체
     *
     * @return array{title:string, content:string, image_query:string, sources:list<array{title:string,url:string}>}|null
     */
    public function generatePost(
        string $faction,
        string $topic,
        string $boardType    = 'azit',
        bool   $useGrounding = false,   // 항상 false — Google Search 그라운딩 미사용
        array  $newsContext  = [],      // RSS 뉴스 목록 (KoreanNewsService 제공)
    ): ?array {
        if (empty($this->apiKey)) {
            $this->lastErrorReason = 'API 키 미설정';
            Log::warning('[GeminiService] API 키 미설정');
            return null;
        }

        $factionLabel = match ($faction) {
            'conservative' => '보수',
            'progressive'  => '진보',
            default        => '중도',
        };
        $boardDesc = $boardType === 'battle'
            ? '전쟁터(전 진영이 토론하는 공간)'
            : '아지트(같은 진영끼리의 커뮤니티)';
        $today = now()->format('Y년 m월 d일');

        // ── 뉴스 컨텍스트 섹션 구성 ─────────────────────────────
        // newsContext가 있으면 프롬프트에 실제 기사 목록 주입,
        // Gemini가 참고한 기사 번호(refs)를 JSON에 반환하도록 지시.
        if (!empty($newsContext)) {
            $newsLines = "\n[오늘의 참고 뉴스]\n";
            $newsLines .= "아래는 실제 보도된 최신 뉴스입니다. 관련 기사를 참고해서 게시글을 작성하세요:\n\n";
            foreach ($newsContext as $idx => $article) {
                $newsLines .= "{$idx}. [{$article['source']}] {$article['title']}\n";
                $newsLines .= "   URL: {$article['url']}\n";
                if (!empty($article['summary'])) {
                    $newsLines .= '   내용: ' . mb_substr($article['summary'], 0, 150) . "\n";
                }
                $newsLines .= "\n";
            }
            $newsBasis = "위 뉴스를 바탕으로";
            $refsNote  = "\n- refs: 실제로 참고한 뉴스 번호 배열 (0부터 시작, 예: [0,2] — 미참고 시 [])";
            $refsJson  = ',"refs":[0]';
        } else {
            $newsLines = '';
            $newsBasis = "최근 1주일 이내의 한국 뉴스·이슈를 반영해서";
            $refsNote  = "\n- refs: 빈 배열 []";
            $refsJson  = ',"refs":[]';
        }

        // ── 프롬프트 구성 ───────────────────────────────────────
        // maxTokens: 4096 — thinkingBudget=0 으로 thinking 비활성화 이후
        //   사고 토큰이 예산을 선점하지 않으므로 4096 토큰 전체가 텍스트 출력에 할당
        //   한국어 800자 게시글 JSON ≈ 1000~1500 토큰 → 4096이면 충분히 안전
        $prompt = <<<PROMPT
오늘은 {$today}입니다.
당신은 한국의 {$factionLabel} 성향 커뮤니티 회원입니다.
{$newsLines}
'{$topic}' 관련하여 {$newsBasis} {$boardDesc}에 올릴 게시글을 작성해주세요.

[핵심 요구사항]
- 제목: 40~100자, 커뮤니티 스타일로 흥미롭게
- 본문: 400~800자, 자연스러운 구어체 한국어
- {$factionLabel} 성향 관점에서 뉴스를 분석·논평
- 뉴스 내용 그대로 복사 금지 → 본인 의견으로 재구성
- 마크다운 사용 금지, 단락(줄바꿈)만 허용
- 이미지에 사용할 영어 키워드 1~3개 (Pixabay 검색용){$refsNote}
- 특정 개인·단체 명예훼손·허위사실 금지
- 혐오·차별 표현 금지

[중요] 반드시 아래 형식의 JSON 단 한 줄로만 응답하세요.
- JSON 앞뒤에 어떤 텍스트도 추가하지 마세요 (설명·마크다운 불필요)
- content 내부의 줄바꿈은 반드시 \\n 문자열로 표현하세요 (실제 개행 금지)
- image_query는 Pixabay에서 검색할 영어 키워드 1~3개 (주제와 직접 관련된 것)

{"title":"제목","content":"본문 단락1\\n\\n단락2","image_query":"english keyword"{$refsJson}}
PROMPT;

        // ── 첫 번째 시도 (항상 그라운딩 없이) ─────────────────────
        $result = $this->callWithRetry($prompt, 0.85, 4096, useGrounding: false);

        if ($result === null) {
            return null;
        }

        $raw    = $this->stripMarkdownFences($result['text']);
        $parsed = $this->extractJsonObject($raw);

        // JSON 파싱 실패 → 한 번 더 재시도
        if ($parsed === null || empty($parsed['title']) || empty($parsed['content'])) {
            Log::info('[GeminiService] JSON 파싱 실패 → 재시도', [
                'raw_length' => strlen($raw),
                'raw_tail'   => mb_substr($raw, -100),
                'raw_head'   => mb_substr($raw, 0, 200),
            ]);
            $retry = $this->callWithRetry($prompt, 0.80, 4096, useGrounding: false);
            if ($retry !== null) {
                $raw2    = $this->stripMarkdownFences($retry['text']);
                $parsed2 = $this->extractJsonObject($raw2);
                if ($parsed2 !== null && !empty($parsed2['title']) && !empty($parsed2['content'])) {
                    $parsed = $parsed2;
                    $raw    = $raw2;
                }
            }
        }

        if ($parsed === null || empty($parsed['title']) || empty($parsed['content'])) {
            // 최후 fallback — Gemini 응답 자체 문제
            Log::warning('[GeminiService] JSON 파싱 최종 실패, 텍스트 분리 fallback', [
                'raw_length'  => strlen($raw),
                'raw_tail'    => mb_substr($raw, -100),
                'raw_snippet' => mb_substr($raw, 0, 300),
            ]);
            $lines  = explode("\n", trim($raw), 2);
            $parsed = [
                'title'       => mb_substr(trim($lines[0] ?? $topic), 0, 250) ?: $topic,
                'content'     => trim($lines[1] ?? '') ?: $topic,
                'image_query' => 'korea politics',
                'refs'        => [],
            ];
        }

        // ── refs → usedSources 변환 ─────────────────────────────
        // Gemini가 반환한 기사 인덱스 배열을 newsContext와 매핑해 출처 목록 구성.
        // 범위 초과 인덱스·중복은 제거.
        $refs        = array_map('intval', (array)($parsed['refs'] ?? []));
        $usedSources = [];
        $seenUrls    = [];
        foreach ($refs as $idx) {
            if ($idx < 0 || $idx >= count($newsContext) || !isset($newsContext[$idx])) {
                continue;
            }
            $article = $newsContext[$idx];
            $urlKey  = md5($article['url']);
            if (!isset($seenUrls[$urlKey])) {
                $seenUrls[$urlKey] = true;
                $usedSources[]     = [
                    'title' => '[' . $article['source'] . '] ' . mb_substr($article['title'], 0, 70),
                    'url'   => $article['url'],
                ];
            }
        }

        return [
            'title'       => mb_substr(trim($parsed['title']), 0, 250),
            'content'     => trim($parsed['content']),
            'image_query' => trim($parsed['image_query'] ?? 'korea politics'),
            'sources'     => $usedSources,
            // youtube_url 제거 — Gemini 할루시네이션으로 존재하지 않는 영상 ID 생성
            // → GenerateAIPostJob에서 YouTube 검색 링크로 대체
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Public: 댓글 생성
    // ─────────────────────────────────────────────────────────

    public function generateComment(
        string $commentFaction,
        string $postTitle,
        string $postContent,
    ): ?string {
        $factionLabel = match ($commentFaction) {
            'conservative' => '보수',
            'progressive'  => '진보',
            default        => '중도',
        };
        $excerpt = mb_substr(strip_tags($postContent), 0, 300);

        $prompt = <<<PROMPT
당신은 한국의 {$factionLabel} 성향 커뮤니티 회원입니다.
아래 게시글에 자연스러운 댓글을 달아주세요.

게시글 제목: {$postTitle}
게시글 내용: {$excerpt}

[요구사항]
- 50~200자, 짧고 자연스러운 구어체 한국어
- {$factionLabel} 성향의 반응 (동조/반박/중립적 코멘트)
- 명예훼손·혐오·허위사실 금지
- 댓글 텍스트만 응답 (JSON 아님, 마크다운 아님)
PROMPT;

        $result = $this->callWithRetry($prompt, 0.9, 300, useGrounding: false);
        if ($result === null) {
            return null;
        }

        $text = $this->stripMarkdownFences($result['text']);
        $text = trim($text, '"\'`');
        return mb_substr(trim($text), 0, 1000);
    }

    // ─────────────────────────────────────────────────────────
    // 내부: 재시도 래퍼
    // ─────────────────────────────────────────────────────────

    /**
     * 그라운딩 실패 시 비그라운딩으로 자동 재시도,
     * 429 Rate-limit 시 지수 백오프 재시도
     *
     * @return array{text:string, sources:array}|null
     */
    private function callWithRetry(
        string $prompt,
        float  $temperature,
        int    $maxTokens,
        bool   $useGrounding = false,
    ): ?array {
        $result = $this->callOnce($prompt, $temperature, $maxTokens, $useGrounding);

        // 그라운딩 실패 → 일반 모드 재시도
        if ($result === null && $useGrounding) {
            Log::info('[GeminiService] 그라운딩 실패 → 일반 모드 재시도', [
                'reason' => $this->lastErrorReason,
            ]);
            $result = $this->callOnce($prompt, $temperature, $maxTokens, useGrounding: false);
        }

        return $result;
    }

    /**
     * 단일 API 호출 + 429 지수 백오프 재시도
     *
     * @return array{text:string, sources:array}|null
     */
    private function callOnce(
        string $prompt,
        float  $temperature,
        int    $maxTokens,
        bool   $useGrounding = false,
    ): ?array {
        $maxAttempts = 3;

        foreach (self::MODELS as $model) {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                $result = $this->httpCall($model, $prompt, $temperature, $maxTokens, $useGrounding);

                if ($result === 'MODEL_NOT_FOUND') {
                    // 이 모델은 사용 불가 → 다음 모델로
                    Log::info("[GeminiService] 모델 {$model} 없음, 다음 모델 시도");
                    break;  // inner for-loop 탈출 → 다음 모델
                }

                if ($result === 'RATE_LIMITED') {
                    $waitSec = min(60, $attempt * 20); // 20s → 40s → 60s
                    Log::warning("[GeminiService] 429 Rate-limit, {$waitSec}초 대기 후 재시도", [
                        'model'   => $model,
                        'attempt' => $attempt,
                    ]);
                    if ($attempt < $maxAttempts) {
                        sleep($waitSec);
                        continue;
                    }
                    $this->lastErrorReason = "Rate-limit (429): {$maxAttempts}회 재시도 후 실패";
                    return null;
                }

                if ($result === null) {
                    // 모델은 있으나 응답 없음 (SAFETY, RECITATION 등)
                    // 다른 모델을 시도해 볼 필요 없이 바로 종료
                    return null;
                }

                // 성공
                return $result;
            }
        }

        $this->lastErrorReason = '모든 Gemini 모델에서 응답 실패';
        Log::error('[GeminiService] 모든 모델 실패');
        return null;
    }

    /**
     * 실제 HTTP 호출
     *
     * @return array{text:string, sources:array}|string|null
     *   - array: 성공
     *   - 'MODEL_NOT_FOUND': 모델이 없음 (404)
     *   - 'RATE_LIMITED': 429 Too Many Requests
     *   - null: 그 외 오류 (콘텐츠 차단 포함)
     */
    private function httpCall(
        string $model,
        string $prompt,
        float  $temperature,
        int    $maxTokens,
        bool   $useGrounding,
    ): array|string|null {
        $url  = sprintf(self::API_BASE, $model) . '?key=' . $this->apiKey;
        $body = [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => $maxTokens,
                'topP'            => 0.95,
                // ✅ 사고(thinking) 모드 명시적 비활성화
                // 문제: gemini-2.5-flash는 기본적으로 thinking이 ON이며,
                //       사고 토큰이 maxOutputTokens 예산을 선점함
                //       → maxTokens=2048 중 ~1948을 사고에 소비 → 텍스트 출력 ~100 토큰만 남음
                //       → finishReason=MAX_TOKENS @ 288 bytes → JSON 잘림 → 제목에 raw JSON 삽입
                // 해결: thinkingBudget=0 으로 비활성화 → 2048 토큰 전체가 텍스트 출력에 할당
                'thinkingConfig'  => ['thinkingBudget' => 0],
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ],
        ];

        // ⚠️ responseMimeType: "application/json" 사용 금지 — 두 가지 치명적 문제:
        //   1. grounding(google_search) + JSON mode → Gemini HTTP 400 에러
        //      ("Tool use with a response mime type: 'application/json' is unsupported")
        //   2. 단독 사용 시에도 출력이 maxOutputTokens 훨씬 이전에 조기 종료(MAX_TOKENS)
        // → JSON 형식은 프롬프트 지시로만 강제. extractJsonObject()가 파싱 보장.

        if ($useGrounding) {
            $body['tools'] = [['google_search' => new \stdClass()]];
        }

        try {
            $response = Http::timeout(self::TIMEOUT)->post($url, $body);

            // ── HTTP 오류 처리 ──────────────────────────────
            if ($response->status() === 404) {
                return 'MODEL_NOT_FOUND';
            }

            if ($response->status() === 429) {
                return 'RATE_LIMITED';
            }

            if (! $response->successful()) {
                $status  = $response->status();
                $snippet = mb_substr($response->body(), 0, 400);

                $this->lastErrorReason = "HTTP {$status}: " . $this->extractApiError($response->body());
                Log::error('[GeminiService] API 오류', [
                    'model'  => $model,
                    'status' => $status,
                    'body'   => $snippet,
                    'grounding' => $useGrounding,
                ]);
                return null;
            }

            // ── 응답 파싱 ────────────────────────────────────
            $data      = $response->json();
            $candidate = $data['candidates'][0] ?? null;

            // 프롬프트 자체가 차단된 경우
            if ($candidate === null) {
                $blockReason = $data['promptFeedback']['blockReason'] ?? null;
                $this->lastErrorReason = $blockReason
                    ? "프롬프트 차단: {$blockReason}"
                    : '응답 candidate 없음';
                Log::warning('[GeminiService] 응답 없음', [
                    'model'       => $model,
                    'blockReason' => $blockReason,
                    'raw'         => mb_substr(json_encode($data), 0, 300),
                ]);
                return null;
            }

            // finishReason 확인
            $finishReason = $candidate['finishReason'] ?? 'STOP';

            if ($finishReason === 'SAFETY') {
                $safetyRatings = $candidate['safetyRatings'] ?? [];
                $blocked = array_column(
                    array_filter($safetyRatings, fn($r) => ($r['blocked'] ?? false) === true),
                    'category',
                );
                $this->lastErrorReason = '안전 필터 차단: ' . (implode(', ', $blocked) ?: 'SAFETY');
                Log::warning('[GeminiService] 안전 필터 차단', [
                    'model'   => $model,
                    'blocked' => $blocked,
                ]);
                return null;
            }

            if ($finishReason === 'RECITATION') {
                $this->lastErrorReason = '저작권 필터 차단 (RECITATION)';
                Log::warning('[GeminiService] 저작권 필터 차단', ['model' => $model]);
                return null;
            }

            if ($finishReason === 'MAX_TOKENS') {
                // thinkingBudget=0 설정 후에도 MAX_TOKENS가 발생하면 maxOutputTokens 부족
                // → JSON 잘림 가능성 있음 (generatePost에서 extractJsonObject 실패 시 fallback 처리)
                $totalTokens = $data['usageMetadata']['totalTokenCount'] ?? null;
                Log::warning('[GeminiService] MAX_TOKENS 조기 종료 — 응답 잘림 가능', [
                    'model'       => $model,
                    'totalTokens' => $totalTokens,
                    'grounding'   => $useGrounding,
                ]);
                // 텍스트는 있을 수 있으므로 계속 진행 (generatePost에서 JSON 파싱 시도)
            }

            // 텍스트 추출
            $text = $candidate['content']['parts'][0]['text'] ?? null;

            if ($text === null || trim($text) === '') {
                $this->lastErrorReason = "텍스트 없음 (finishReason={$finishReason})";
                Log::warning('[GeminiService] 텍스트 비어 있음', [
                    'model'        => $model,
                    'finishReason' => $finishReason,
                    'candidate'    => mb_substr(json_encode($candidate), 0, 300),
                ]);
                return null;
            }

            // 그라운딩 소스 추출
            $sources = [];
            $chunks  = $data['candidates'][0]['groundingMetadata']['groundingChunks'] ?? [];
            foreach ($chunks as $chunk) {
                $web = $chunk['web'] ?? null;
                if ($web && ! empty($web['uri'])) {
                    $sources[] = [
                        'title' => $web['title'] ?? $web['uri'],
                        'url'   => $web['uri'],
                    ];
                }
            }

            Log::debug('[GeminiService] 성공', [
                'model'        => $model,
                'finishReason' => $finishReason,
                'tokens'       => $data['usageMetadata']['totalTokenCount'] ?? null,
                'sources'      => count($sources),
            ]);

            return ['text' => $text, 'sources' => array_slice($sources, 0, 5)];

        } catch (\Throwable $e) {
            $this->lastErrorReason = '예외: ' . $e->getMessage();
            Log::error('[GeminiService] 예외 발생', [
                'model'   => $model,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────
    // 헬퍼
    // ─────────────────────────────────────────────────────────

    private function stripMarkdownFences(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        return trim($text);
    }

    /**
     * 텍스트에서 JSON 객체를 추출합니다.
     *
     * Gemini가 JSON 앞뒤에 불필요한 텍스트를 붙이거나,
     * 문자열 값 내부에 실제 줄바꿈을 삽입하는 경우에도 올바르게 파싱합니다.
     *
     * 시도 순서:
     *   1. 전체 텍스트 직접 파싱
     *   2. 첫 번째 { ~ 마지막 } 추출 후 직접 파싱
     *   3. 상태머신으로 문자열 내 줄바꿈 이스케이프 후 재파싱
     */
    private function extractJsonObject(string $text): ?array
    {
        // 시도 1: 직접 파싱
        $data = json_decode($text, true);
        if (is_array($data)) {
            return $data;
        }

        // { ... } 경계 탐색
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $candidate = substr($text, $start, $end - $start + 1);

        // 시도 2: 추출 후 직접 파싱
        $data = json_decode($candidate, true);
        if (is_array($data)) {
            return $data;
        }

        // 시도 3: 상태머신으로 JSON 문자열 내 실제 줄바꿈·제어문자 이스케이프
        // Gemini가 content 값 안에 literal \n 을 넣는 경우를 커버
        $fixed = $this->fixJsonControlChars($candidate);
        $data  = json_decode($fixed, true);
        if (is_array($data)) {
            return $data;
        }

        return null;
    }

    /**
     * JSON 문자열 필드 내의 비이스케이프 제어문자(줄바꿈 등)를 이스케이프.
     *
     * 상태머신 방식으로 파싱해 문자열 범위 외에는 건드리지 않는다.
     * - \n  → \\n
     * - \r  → \\r  (혹은 \r\n → \\n)
     * - \t  → \\t
     */
    private function fixJsonControlChars(string $json): string
    {
        $result   = '';
        $inString = false;
        $len      = strlen($json); // byte-safe (JSON is ASCII backbone)

        for ($i = 0; $i < $len; $i++) {
            $ch = $json[$i];

            if ($inString) {
                if ($ch === '\\') {
                    // 이스케이프 시퀀스: 다음 문자와 함께 그대로 통과
                    $result .= $ch;
                    if ($i + 1 < $len) {
                        $result .= $json[++$i];
                    }
                    continue;
                }

                if ($ch === '"') {
                    $inString = false;
                    $result  .= $ch;
                    continue;
                }

                // 문자열 내부의 제어문자를 이스케이프
                if ($ch === "\n") {
                    $result .= '\\n';
                    continue;
                }
                if ($ch === "\r") {
                    // \r\n 쌍이면 \n 하나로 처리
                    if ($i + 1 < $len && $json[$i + 1] === "\n") {
                        $i++;
                    }
                    $result .= '\\n';
                    continue;
                }
                if ($ch === "\t") {
                    $result .= '\\t';
                    continue;
                }

                $result .= $ch;
            } else {
                if ($ch === '"') {
                    $inString = true;
                }
                $result .= $ch;
            }
        }

        return $result;
    }

    /** API 오류 응답에서 사람이 읽을 수 있는 메시지 추출 */
    private function extractApiError(string $body): string
    {
        $data    = json_decode($body, true);
        $message = $data['error']['message'] ?? null;
        if ($message) {
            return mb_substr($message, 0, 200);
        }
        return mb_substr($body, 0, 200);
    }
}
