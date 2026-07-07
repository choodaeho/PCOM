<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini API 래퍼 (무료 티어 최적화)
 *
 * 무료 티어: 15 RPM / 1M tokens/day
 * 모델 우선순위: gemini-2.0-flash-lite → gemini-2.0-flash → gemini-1.5-flash
 * 429 Rate-limit 시 지수 백오프 재시도 (최대 3회)
 * 안전 필터 차단(SAFETY) / 저작권(RECITATION) 시 자동 재시도
 */
class GeminiService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const TIMEOUT  = 60;

    /**
     * 모델 우선순위 목록
     * 앞에서부터 시도 → 404(모델 없음) 시 다음 모델로 자동 전환
     *
     * ⚠️ gemini-1.5-flash 계열은 v1beta에서 deprecated → 사용 금지
     * 실제 사용 가능 모델은 아래 명령으로 조회:
     *   docker compose exec app php artisan tinker --execute="
     *     \$r = Http::get('https://generativelanguage.googleapis.com/v1beta/models?key='.config('services.gemini.api_key'));
     *     foreach(\$r->json()['models'] as \$m) {
     *       if(in_array('generateContent',\$m['supportedGenerationMethods']??[])) echo \$m['name'].PHP_EOL;
     *     }
     *   "
     */
    private const MODELS = [
        'gemini-2.5-flash',        // 최신 무료 모델 (2025.03~)
        'gemini-2.0-flash',        // 안정 무료 모델
        'gemini-2.0-flash-lite',   // 경량 무료 모델
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
     * @return array{title:string, content:string, image_query:string, youtube_url:string, sources:array}|null
     */
    public function generatePost(
        string $faction,
        string $topic,
        string $boardType    = 'azit',
        bool   $useGrounding = true,
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

        $prompt = <<<PROMPT
오늘은 {$today}입니다.
당신은 한국의 {$factionLabel} 성향 커뮤니티 회원입니다.

'{$topic}' 관련하여 최근 1주일 이내의 한국 뉴스·이슈를 반영해서
{$boardDesc}에 올릴 게시글을 작성해주세요.

[핵심 요구사항]
- 제목: 40~100자, 커뮤니티 스타일로 흥미롭게
- 본문: 400~800자, 자연스러운 구어체 한국어
- {$factionLabel} 성향 관점에서 최신 뉴스를 분석·논평
- 뉴스 내용을 그대로 복사하지 말고 본인 의견으로 재구성
- 특정 개인·단체에 대한 명예훼손·허위사실 작성 금지
- 혐오·차별 표현 금지
- 마크다운 사용 금지, 단락(줄바꿈)만 허용
- 이미지에 사용할 영어 키워드 1~3개 (Pixabay 검색용)
- 주제와 관련된 실제 유튜브 동영상 URL 1개 (https://www.youtube.com/watch?v=... 형식)
  → 실제로 존재하는 뉴스·시사 영상 URL을 제공하고, 없으면 null

[중요] 반드시 아래 형식의 JSON 단 한 줄로만 응답하세요.
- JSON 앞뒤에 어떤 텍스트도 추가하지 마세요 (설명·마크다운 불필요)
- content 내부의 줄바꿈은 반드시 \\n 문자열로 표현하세요 (실제 개행 금지)
- image_query는 Pixabay에서 검색할 영어 키워드 1~3개 (주제와 직접 관련된 것)
- youtube_url이 없으면 null 문자열로 입력하세요

{"title":"제목","content":"본문 단락1\\n\\n단락2","image_query":"english keyword","youtube_url":"https://www.youtube.com/watch?v=VIDEO_ID"}
PROMPT;

        // 그라운딩 시도 → 실패 시 비그라운딩으로 자동 재시도
        $result = $this->callWithRetry($prompt, 0.85, 1200, useGrounding: $useGrounding);

        if ($result === null) {
            return null;
        }

        $raw    = $this->stripMarkdownFences($result['text']);
        $parsed = $this->extractJsonObject($raw);

        if ($parsed === null || empty($parsed['title']) || empty($parsed['content'])) {
            // 최후 fallback: 첫 줄을 제목, 나머지를 본문으로
            Log::warning('[GeminiService] JSON 파싱 실패, 텍스트 분리 fallback', [
                'raw_snippet' => mb_substr($raw, 0, 200),
            ]);
            $lines  = explode("\n", trim($raw), 2);
            $parsed = [
                'title'       => mb_substr(trim($lines[0] ?? $topic), 0, 250) ?: $topic,
                'content'     => trim($lines[1] ?? '') ?: $topic,
                'image_query' => 'korea politics',
                'youtube_url' => null,
            ];
        }

        // youtube_url 정규화 — "null" 문자열, 빈값 처리
        $youtubeUrl = trim($parsed['youtube_url'] ?? '');
        if ($youtubeUrl === 'null' || $youtubeUrl === 'NULL') {
            $youtubeUrl = '';
        }

        return [
            'title'       => mb_substr(trim($parsed['title']), 0, 250),
            'content'     => trim($parsed['content']),
            'image_query' => trim($parsed['image_query'] ?? 'korea politics'),
            'youtube_url' => $youtubeUrl,
            'sources'     => $result['sources'] ?? [],
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
            ],
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
            ],
        ];

        if ($useGrounding) {
            $body['tools'] = [['google_search' => new \stdClass()]];
        } else {
            // 그라운딩 미사용 시 JSON 응답 타입 강제 → 마크다운 펜스·접두 텍스트 제거
            // (그라운딩 + responseMimeType 동시 사용 불가)
            $body['generationConfig']['responseMimeType'] = 'application/json';
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
