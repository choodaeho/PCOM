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

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'board_id',
        'faction',
        'title',
        'content',
        'attachments',
        'status',
        'is_notice',
        'is_anonymous',
        'view_count',
        'comment_count',
        'vote_up_count',
        'vote_down_count',
        'report_count',
    ];

    protected function casts(): array
    {
        return [
            'faction'        => FactionType::class,
            'status'         => PostStatus::class,
            'attachments'    => 'array',   // JSONB → PHP array
            'is_notice'      => 'boolean',
            'is_anonymous'   => 'boolean',
            'view_count'     => 'integer',
            'comment_count'  => 'integer',
            'vote_up_count'  => 'integer',
            'vote_down_count'=> 'integer',
            'report_count'   => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // 관계
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
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

    public function scopeByFaction(
        \Illuminate\Database\Eloquent\Builder $query,
        FactionType $faction
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('faction', $faction->value);
    }

    /**
     * 전문 검색(Full-Text Search).
     */
    public function scopeSearch(
        \Illuminate\Database\Eloquent\Builder $query,
        string $keyword
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereRaw(
            "search_vector @@ plainto_tsquery('simple', ?)",
            [$keyword]
        )->orderByRaw(
            "ts_rank(search_vector, plainto_tsquery('simple', ?)) DESC",
            [$keyword]
        );
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 조회수 증가 (Redis 버퍼링 없이 직접 증가).
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * 현재 사용자가 이 게시글에 투표했는지 확인.
     */
    public function hasVotedBy(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    /**
     * 표시용 작성자명 (익명 처리).
     */
    public function authorName(): string
    {
        if ($this->is_anonymous) {
            return '익명';
        }
        return $this->user?->nickname ?? '알 수 없음';
    }
}
