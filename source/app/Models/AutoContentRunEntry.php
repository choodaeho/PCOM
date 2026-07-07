<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AI 자동 생성 개별 항목 로그 (게시글 1건 or 댓글 1건 = 1 row)
 *
 * @property int     $id
 * @property int     $run_id
 * @property string  $entry_type     post|comment
 * @property ?string $faction
 * @property ?int    $user_id
 * @property ?string $nickname
 * @property ?string $board_slug
 * @property ?string $board_name
 * @property ?int    $post_id
 * @property ?int    $parent_post_id
 * @property ?string $topic
 * @property ?string $title
 * @property string  $status         success|failed|skipped
 * @property ?string $error_message
 * @property ?int    $duration_ms
 * @property ?\Carbon\Carbon $scheduled_at
 * @property ?\Carbon\Carbon $executed_at
 */
class AutoContentRunEntry extends Model
{
    protected $fillable = [
        'run_id',
        'entry_type',
        'faction',
        'user_id',
        'nickname',
        'board_slug',
        'board_name',
        'post_id',
        'parent_post_id',
        'topic',
        'title',
        'status',
        'error_message',
        'duration_ms',
        'scheduled_at',
        'executed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at'  => 'datetime',
    ];

    // ── 관계 ──────────────────────────────────────────

    public function run(): BelongsTo
    {
        return $this->belongsTo(AutoContentRun::class, 'run_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ── 헬퍼 ──────────────────────────────────────────

    /** 진영 한글 레이블 */
    public function getFactionLabelAttribute(): string
    {
        return match ($this->faction) {
            'conservative' => '보수',
            'moderate'     => '중도',
            'progressive'  => '진보',
            default        => $this->faction ?? '-',
        };
    }

    /** 소요 시간 포맷 (1.2s) */
    public function getDurationFormattedAttribute(): ?string
    {
        if ($this->duration_ms === null) {
            return null;
        }
        return round($this->duration_ms / 1000, 1) . 's';
    }
}
