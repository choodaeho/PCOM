<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FactionType;
use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'faction',
        'content',
        'is_anonymous',
        'status',
        'vote_up_count',
        'vote_down_count',
        'report_count',
        'reply_count',
    ];

    protected function casts(): array
    {
        return [
            'faction'         => FactionType::class,
            'status'          => PostStatus::class,
            'is_anonymous'    => 'boolean',
            'vote_up_count'   => 'integer',
            'vote_down_count' => 'integer',
            'report_count'    => 'integer',
            'reply_count'     => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // 관계
    // -------------------------------------------------------------------------

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 대댓글 parent */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /** 대댓글 목록 (1-depth) */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    /** Polymorphic 추천/비추천 */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /** Polymorphic 신고 */
    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', PostStatus::Published->value);
    }

    /** 최상위 댓글만 (대댓글 제외) */
    public function scopeTopLevel(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('parent_id');
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function authorName(): string
    {
        if ($this->is_anonymous) {
            return '익명';
        }
        return $this->user?->nickname ?? '알 수 없음';
    }
}
