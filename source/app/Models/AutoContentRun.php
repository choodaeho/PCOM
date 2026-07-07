<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AI 자동 생성 실행 이력 (1회 실행 = 1 row)
 *
 * @property int    $id
 * @property string $run_date
 * @property string $run_type          scheduled|manual|dry_run
 * @property ?int   $triggered_by
 * @property string $status            running|completed|failed
 * @property int    $posts_dispatched
 * @property int    $posts_succeeded
 * @property int    $posts_failed
 * @property int    $comments_dispatched
 * @property int    $comments_succeeded
 * @property int    $comments_failed
 * @property \Carbon\Carbon  $started_at
 * @property ?\Carbon\Carbon $completed_at
 * @property ?\Carbon\Carbon $last_activity_at
 * @property ?string $notes
 */
class AutoContentRun extends Model
{
    protected $fillable = [
        'run_date',
        'run_type',
        'triggered_by',
        'status',
        'is_stopped',
        'stopped_at',
        'posts_dispatched',
        'posts_succeeded',
        'posts_failed',
        'posts_skipped',
        'comments_dispatched',
        'comments_succeeded',
        'comments_failed',
        'comments_skipped',
        'started_at',
        'completed_at',
        'last_activity_at',
        'notes',
    ];

    protected $casts = [
        'run_date'         => 'date',
        'is_stopped'       => 'boolean',
        'started_at'       => 'datetime',
        'stopped_at'       => 'datetime',
        'completed_at'     => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    // ── 관계 ──────────────────────────────────────────

    public function entries(): HasMany
    {
        return $this->hasMany(AutoContentRunEntry::class, 'run_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    // ── 카운터 헬퍼 ───────────────────────────────────

    /** 게시글 성공 카운터 +1 및 활동 시각 갱신 */
    public function recordPostSuccess(): void
    {
        $this->increment('posts_succeeded');
        $this->update(['last_activity_at' => now()]);
    }

    /** 게시글 실패 카운터 +1 및 활동 시각 갱신 */
    public function recordPostFailed(): void
    {
        $this->increment('posts_failed');
        $this->update(['last_activity_at' => now()]);
    }

    /** 댓글 성공 카운터 +1 및 활동 시각 갱신 */
    public function recordCommentSuccess(): void
    {
        $this->increment('comments_succeeded');
        $this->update(['last_activity_at' => now()]);
    }

    /** 댓글 실패 카운터 +1 및 활동 시각 갱신 */
    public function recordCommentFailed(): void
    {
        $this->increment('comments_failed');
        $this->update(['last_activity_at' => now()]);
    }

    /** 게시글 건너뜀 카운터 +1 (중지로 인한 skip) */
    public function recordPostSkipped(): void
    {
        $this->increment('posts_skipped');
    }

    /** 댓글 건너뜀 카운터 +1 (중지로 인한 skip) */
    public function recordCommentSkipped(): void
    {
        $this->increment('comments_skipped');
    }

    /** 중지 요청 */
    public function stop(): void
    {
        $this->update([
            'is_stopped' => true,
            'stopped_at' => now(),
            'status'     => 'stopping',
        ]);
    }

    /** 실행 중 여부 (중지 가능한 상태) */
    public function isStoppable(): bool
    {
        return in_array($this->status, ['running', 'completed'], true) && ! $this->is_stopped;
    }

    // ── 계산 프로퍼티 ─────────────────────────────────

    /** 전체 오류 수 */
    public function getTotalErrorsAttribute(): int
    {
        return $this->posts_failed + $this->comments_failed;
    }

    /** 게시글 성공률 (%) */
    public function getPostSuccessRateAttribute(): float
    {
        if ($this->posts_dispatched === 0) {
            return 0.0;
        }
        return round($this->posts_succeeded / $this->posts_dispatched * 100, 1);
    }

    /** 댓글 성공률 (%) */
    public function getCommentSuccessRateAttribute(): float
    {
        if ($this->comments_dispatched === 0) {
            return 0.0;
        }
        return round($this->comments_succeeded / $this->comments_dispatched * 100, 1);
    }

    /** 실행 경과 시간 (초) */
    public function getElapsedSecondsAttribute(): ?int
    {
        if (! $this->last_activity_at) {
            return null;
        }
        return (int) $this->started_at->diffInSeconds($this->last_activity_at);
    }
}
