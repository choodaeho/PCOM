<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AutoContentRun;
use App\Models\AutoContentRunEntry;
use App\Models\Board;
use App\Models\Post;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\NewsImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AI 게시글 1건 생성 Job
 *
 * 생성 흐름:
 *   1. Gemini (+ Google Search 그라운딩) → 최신 뉴스 기반 제목·본문·소스 생성
 *   2. NewsImageService → 관련 이미지 URL 조회 (Pixabay → picsum fallback)
 *   3. HTML 조립: 본문 + 이미지 + 뉴스 출처 링크 + YouTube iframe 임베드
 *   4. Post 레코드 생성
 *   5. 댓글 GenerateAICommentJob 예약
 *   6. AutoContentRunEntry 로그 기록
 */
class GenerateAIPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Rate-limit release 시 tries 차감 없이 무한 재큐잉되므로
    // 실제 예외 실패(maxExceptions)와 분리하여 관리한다.
    public int $tries         = 50;   // Rate-limit release 포함한 충분한 시도 횟수
    public int $maxExceptions = 3;    // 실제 RuntimeException 3회 시 최종 실패
    public int $timeout       = 300;  // Gemini 429 재시도(최대 120s) + API 응답 여유
    public int $backoff       = 300;

    public function __construct(
        private readonly int     $userId,
        private readonly int     $boardId,
        private readonly string  $faction,
        private readonly string  $topic,
        private readonly string  $geminiApiKey,
        private readonly string  $pixabayApiKey,
        private readonly bool    $useGrounding,
        private readonly bool    $includeImages,
        private readonly bool    $includeNewsLinks,
        private readonly bool    $includeYoutube,
        private readonly int     $commentCount,
        private readonly int     $commentDelayMin,
        private readonly int     $commentDelayMax,
        private readonly array   $commentUserIds,
        private readonly ?int    $runId           = null,  // 실행 이력 ID
        private readonly ?string $scheduledAt     = null,  // 예약 시각 ISO 문자열
    ) {}

    /**
     * Gemini API Rate Limiter 미들웨어.
     *
     * AppServiceProvider에서 등록한 'gemini' 리미터(10/min)를 적용한다.
     * 한도 초과 시 job을 큐에 release → tries 차감 없이 다음 슬롯에서 재처리.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('gemini')];
    }

    public function handle(): void
    {
        $startedAt = now();
        $startMs   = hrtime(true);

        // ── 중지 플래그 확인 ─────────────────────────────────
        if ($this->runId !== null) {
            $runCheck = AutoContentRun::find($this->runId);
            if ($runCheck?->is_stopped) {
                Log::info('[GenerateAIPostJob] 실행 중지됨, 건너뜀', [
                    'run_id' => $this->runId,
                    'faction' => $this->faction,
                ]);
                $runCheck->recordPostSkipped();
                return; // 실패 처리 없이 조용히 종료
            }
        }

        $user  = User::find($this->userId);
        $board = Board::find($this->boardId);

        if (! $user || ! $board) {
            Log::warning('[GenerateAIPostJob] 유저/게시판 없음', [
                'user_id' => $this->userId, 'board_id' => $this->boardId,
            ]);
            return;
        }

        // ── 1. Gemini로 게시글 생성 ──────────────────────────
        $gemini    = new GeminiService($this->geminiApiKey);
        $boardType = str_contains($board->slug, 'azit') ? 'azit' : 'battle';

        $result = $gemini->generatePost(
            $this->faction,
            $this->topic,
            $boardType,
            $this->useGrounding,
        );

        if ($result === null) {
            $reason = $gemini->getLastErrorReason();
            $msg    = 'Gemini 응답 없음' . ($reason !== '' ? ": {$reason}" : '');
            Log::error('[GenerateAIPostJob] ' . $msg, [
                'faction' => $this->faction, 'topic' => $this->topic,
            ]);
            $this->writeEntry(
                board: $board,
                user: $user,
                status: 'failed',
                errorMessage: $msg,
                durationMs: $this->elapsedMs($startMs),
                executedAt: $startedAt,
            );
            $this->fail(new \RuntimeException($msg));
            return;
        }

        // ── 2. 이미지 URL 조회 ────────────────────────────────
        // 40% 확률로만 이미지 포함 — 모든 글에 이미지를 넣으면 주제와 무관한
        // Pixabay 결과가 노출될 수 있으므로 선별적으로 첨부.
        $imageUrl     = null;
        $useImage     = $this->includeImages && (mt_rand(1, 10) <= 4);
        $imageQuery   = trim($result['image_query'] ?? '');

        if ($useImage && $imageQuery !== '' && !empty($this->pixabayApiKey)) {
            $imageService = new NewsImageService($this->pixabayApiKey);
            $imageUrl     = $imageService->fetchImageUrl($imageQuery, 800);
            // 검색 결과가 없으면 이미지 제외 (null 그대로 유지)
        }

        // ── 3. HTML 조립 ──────────────────────────────────────
        $contentHtml = $this->buildHtml(
            body:       $result['content'],
            imageUrl:   $imageUrl,
            sources:    $result['sources'] ?? [],
            youtubeUrl: $result['youtube_url'] ?? '',
        );

        // ── 4. Post 생성 ──────────────────────────────────────
        /** @var Post $post */
        $post = Post::create([
            'user_id'  => $this->userId,
            'board_id' => $this->boardId,
            'faction'  => $this->faction,
            'title'    => $result['title'],
            'content'  => $contentHtml,
            'status'   => 'published',
        ]);

        $durationMs = $this->elapsedMs($startMs);

        Log::info('[GenerateAIPostJob] 게시글 생성', [
            'post_id' => $post->id,
            'title'   => mb_substr($post->title, 0, 50),
            'sources' => count($result['sources'] ?? []),
            'image'   => $imageUrl ? 'yes' : 'no',
            'run_id'  => $this->runId,
        ]);

        // ── 5. 실행 이력 로그 ────────────────────────────────
        $this->writeEntry(
            board: $board,
            user: $user,
            status: 'success',
            errorMessage: null,
            durationMs: $durationMs,
            executedAt: $startedAt,
            post: $post,
            title: $result['title'],
        );

        if ($this->runId !== null) {
            AutoContentRun::find($this->runId)?->recordPostSuccess();
        }

        // ── 6. 댓글 Job 예약 ──────────────────────────────────
        foreach (array_slice($this->commentUserIds, 0, $this->commentCount) as $idx => $commentUserId) {
            $delay = random_int($this->commentDelayMin, $this->commentDelayMax)
                   + ($idx * random_int(2, 5));

            $commentScheduledAt = now()->addMinutes($delay);

            GenerateAICommentJob::dispatch(
                $post->id,
                $commentUserId,
                $post->title,
                mb_substr(strip_tags($contentHtml), 0, 300),
                $this->geminiApiKey,
                $this->runId,
                $commentScheduledAt->toISOString(),
            )->delay($commentScheduledAt);
        }
    }

    /**
     * Job 최종 실패 시 호출 (retry 소진 후)
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[GenerateAIPostJob] Job 최종 실패', [
            'faction' => $this->faction,
            'topic'   => $this->topic,
            'error'   => $exception->getMessage(),
            'run_id'  => $this->runId,
        ]);

        if ($this->runId !== null) {
            // 실패 entry 기록 (handle()에서 이미 기록됐을 수 있으므로 중복 방지)
            $run = AutoContentRun::find($this->runId);
            if ($run) {
                // posts_failed 카운터는 handle() 내에서 writeEntry()와 함께 올리지 않으므로 여기서만 증가
                $run->recordPostFailed();
            }
        }
    }

    // ── private 헬퍼 ──────────────────────────────────────

    /** 실행 이력 entry 기록 */
    private function writeEntry(
        Board   $board,
        User    $user,
        string  $status,
        ?string $errorMessage,
        ?int    $durationMs,
        \Carbon\Carbon $executedAt,
        ?Post   $post    = null,
        ?string $title   = null,
    ): void {
        if ($this->runId === null) {
            return;
        }

        AutoContentRunEntry::create([
            'run_id'       => $this->runId,
            'entry_type'   => 'post',
            'faction'      => $this->faction,
            'user_id'      => $user->id,
            'nickname'     => $user->nickname,
            'board_slug'   => $board->slug,
            'board_name'   => $board->name,
            'post_id'      => $post?->id,
            'topic'        => mb_substr($this->topic, 0, 300),
            'title'        => $title ? mb_substr($title, 0, 300) : null,
            'status'       => $status,
            'error_message'=> $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
            'duration_ms'  => $durationMs,
            'scheduled_at' => $this->scheduledAt,
            'executed_at'  => $executedAt,
        ]);
    }

    /** hrtime 기반 경과 ms 계산 */
    private function elapsedMs(int $startNs): int
    {
        return (int) ((hrtime(true) - $startNs) / 1_000_000);
    }

    /**
     * 리치 HTML 본문 조립
     *
     * 구조:
     *   <p>본문 단락들...</p>
     *   <img ...> (옵션)
     *   <p>본문 계속...</p>
     *   <hr>
     *   뉴스 출처 링크 (옵션)
     *   YouTube iframe 임베드 (옵션) — Quill ql-video 형식
     */
    private function buildHtml(
        string  $body,
        ?string $imageUrl,
        array   $sources,
        string  $youtubeUrl,
    ): string {
        $html = '';

        // 본문 → 단락 분리 (줄바꿈 기준)
        $paragraphs = array_filter(
            array_map('trim', preg_split('/\n{1,}/', $body)),
            fn($p) => strlen($p) > 0,
        );
        $totalParas = count($paragraphs);

        // 이미지 삽입 위치: 두 번째 단락 뒤 (없으면 첫 번째 뒤)
        $insertAfter = min(1, $totalParas - 1);

        foreach ($paragraphs as $idx => $para) {
            $html .= '<p>' . e($para) . '</p>' . "\n";

            // 이미지 삽입
            if ($imageUrl && $idx === $insertAfter) {
                $html .= $this->buildImageTag($imageUrl) . "\n";
            }
        }

        // 이미지가 단락 이후에도 추가 안 됐다면 본문 끝에 추가
        if ($imageUrl && $totalParas === 0) {
            $html .= $this->buildImageTag($imageUrl) . "\n";
        }

        // ── 뉴스 출처 + YouTube 푸터 ──────────────────────────
        $footer = $this->buildFooter($sources, $youtubeUrl);
        if ($footer !== '') {
            $html .= "\n<hr style=\"border:none;border-top:1px solid #e2e8f0;margin:20px 0\">\n";
            $html .= $footer;
        }

        return $html;
    }

    private function buildImageTag(string $url): string
    {
        return sprintf(
            '<figure style="margin:16px 0;text-align:center">'
            . '<img src="%s" alt="관련 이미지" '
            . 'style="max-width:100%%;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.15)" '
            . 'loading="lazy">'
            . '</figure>',
            e($url),
        );
    }

    private function buildFooter(array $sources, string $youtubeUrl): string
    {
        $html = '';

        // 뉴스 출처 링크 (그라운딩에서 가져온 실제 URL)
        $validSources = array_filter(
            $sources,
            fn($s) => ! empty($s['url']) && filter_var($s['url'], FILTER_VALIDATE_URL),
        );

        if ($this->includeNewsLinks && ! empty($validSources)) {
            $html .= '<div style="font-size:13px;color:#64748b;margin-top:12px">';
            $html .= '<strong>📰 관련 뉴스</strong>';
            $html .= '<ul style="margin:6px 0 0;padding-left:18px;line-height:1.8">';
            foreach (array_slice(array_values($validSources), 0, 4) as $src) {
                $title = e(mb_substr($src['title'], 0, 80));
                $url   = e($src['url']);
                $html .= "<li><a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" "
                       . "style=\"color:#6366f1;text-decoration:none\">{$title}</a></li>";
            }
            $html .= '</ul></div>';
        }

        // YouTube 임베드 — Quill ql-video iframe 형식
        if ($this->includeYoutube && ! empty(trim($youtubeUrl))) {
            $embedUrl = $this->youtubeEmbedUrl($youtubeUrl);
            if ($embedUrl !== null) {
                // Quill 에디터 동영상 삽입 형식 (ql-video)
                $html .= '<iframe class="ql-video" frameborder="0" allowfullscreen="true" src="'
                       . e($embedUrl) . '"></iframe>' . "\n";
            }
        }

        return $html;
    }

    /**
     * YouTube URL에서 embed URL 추출
     *
     * 지원 형식:
     *   https://www.youtube.com/watch?v=VIDEO_ID
     *   https://youtu.be/VIDEO_ID
     *   https://www.youtube.com/embed/VIDEO_ID
     *
     * @return string|null  embed URL 또는 null (유효하지 않은 URL)
     */
    private function youtubeEmbedUrl(string $url): ?string
    {
        if (preg_match(
            '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/',
            $url,
            $m,
        )) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        return null;
    }
}
