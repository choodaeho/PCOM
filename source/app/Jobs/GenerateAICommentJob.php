<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AutoContentRun;
use App\Models\AutoContentRunEntry;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * AI 댓글 1건 생성 Job
 */
class GenerateAICommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries         = 50;   // Rate-limit release 포함한 충분한 시도 횟수
    public int $maxExceptions = 3;    // 실제 RuntimeException 3회 시 최종 실패
    public int $timeout       = 180;  // Gemini API 응답 + 429 재시도 여유
    public int $backoff       = 180;

    public function __construct(
        private readonly int     $postId,
        private readonly int     $userId,
        private readonly string  $postTitle,
        private readonly string  $postContent,
        private readonly string  $geminiApiKey,
        private readonly ?int    $runId       = null,   // 실행 이력 ID
        private readonly ?string $scheduledAt = null,   // 예약 시각 ISO 문자열
    ) {}

    /**
     * Gemini API Rate Limiter 미들웨어 (PostJob과 동일한 'gemini' 버킷 공유).
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
                Log::info('[GenerateAICommentJob] 실행 중지됨, 건너뜀', [
                    'run_id'  => $this->runId,
                    'post_id' => $this->postId,
                ]);
                $runCheck->recordCommentSkipped();
                return;
            }
        }

        $post = Post::find($this->postId);
        $user = User::find($this->userId);

        if (! $post || ! $user) {
            Log::warning('[GenerateAICommentJob] 게시글/유저 없음', [
                'post_id' => $this->postId, 'user_id' => $this->userId,
            ]);
            return;
        }

        // 게시글이 삭제/숨김 처리된 경우 건너뜀
        if ($post->status !== 'published') {
            $this->writeEntry($post, $user, 'skipped', '게시글이 삭제/숨김 처리됨', null, $startedAt);
            return;
        }

        $faction = is_object($user->political_type)
            ? $user->political_type->value
            : (string) $user->political_type;

        $gemini  = new GeminiService($this->geminiApiKey);
        $content = $gemini->generateComment($faction, $this->postTitle, $this->postContent);

        if ($content === null || strlen(trim($content)) < 5) {
            $reason = $gemini->getLastErrorReason();
            $msg    = 'Gemini 댓글 응답 없음' . ($reason !== '' ? ": {$reason}" : '');
            Log::error('[GenerateAICommentJob] ' . $msg, ['post_id' => $this->postId]);
            $this->writeEntry($post, $user, 'failed', $msg, $this->elapsedMs($startMs), $startedAt);
            $this->fail(new \RuntimeException($msg));
            return;
        }

        Comment::create([
            'post_id'      => $post->id,
            'user_id'      => $user->id,
            'faction'      => $faction,
            'content'      => $content,
            'parent_id'    => null,
            'reply_to_id'  => null,
            'is_anonymous' => false,
        ]);

        // 댓글 카운터 동기화
        $post->increment('comment_count');

        $durationMs = $this->elapsedMs($startMs);

        Log::info('[GenerateAICommentJob] 댓글 생성', [
            'post_id' => $this->postId,
            'user_id' => $this->userId,
            'run_id'  => $this->runId,
        ]);

        // 실행 이력 로그
        $this->writeEntry($post, $user, 'success', null, $durationMs, $startedAt);

        if ($this->runId !== null) {
            AutoContentRun::find($this->runId)?->recordCommentSuccess();
        }
    }

    /**
     * Job 최종 실패 시 호출 (retry 소진 후)
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[GenerateAICommentJob] Job 최종 실패', [
            'post_id' => $this->postId,
            'error'   => $exception->getMessage(),
            'run_id'  => $this->runId,
        ]);

        if ($this->runId !== null) {
            AutoContentRun::find($this->runId)?->recordCommentFailed();
        }
    }

    // ── private 헬퍼 ──────────────────────────────────────

    private function writeEntry(
        Post    $post,
        User    $user,
        string  $status,
        ?string $errorMessage,
        ?int    $durationMs,
        \Carbon\Carbon $executedAt,
    ): void {
        if ($this->runId === null) {
            return;
        }

        $faction = is_object($user->political_type)
            ? $user->political_type->value
            : (string) $user->political_type;

        AutoContentRunEntry::create([
            'run_id'         => $this->runId,
            'entry_type'     => 'comment',
            'faction'        => $faction,
            'user_id'        => $user->id,
            'nickname'       => $user->nickname,
            'post_id'        => null,  // 댓글 자체의 ID는 없음
            'parent_post_id' => $post->id,
            'title'          => mb_substr($this->postTitle, 0, 300),
            'status'         => $status,
            'error_message'  => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
            'duration_ms'    => $durationMs,
            'scheduled_at'   => $this->scheduledAt,
            'executed_at'    => $executedAt,
        ]);
    }

    private function elapsedMs(int $startNs): int
    {
        return (int) ((hrtime(true) - $startNs) / 1_000_000);
    }
}
