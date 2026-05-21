<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poll extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'options',
        'is_active',
        'starts_at',
        'ends_at',
        'total_vote_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'options'          => 'array',   // JSONB → PHP array [{id, label, vote_count}]
            'is_active'        => 'boolean',
            'starts_at'        => 'datetime',
            'ends_at'          => 'datetime',
            'total_vote_count' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // 관계
    // -------------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            });
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 현재 진행 중인 투표인지 여부.
     */
    public function isOngoing(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }
        return true;
    }

    /**
     * 진영별 투표 현황 집계 반환.
     * [
     *   'conservative' => [option_id => count, ...],
     *   'moderate'     => [option_id => count, ...],
     *   'progressive'  => [option_id => count, ...],
     * ]
     *
     * @return array<string, array<int, int>>
     */
    public function voteStatsByFaction(): array
    {
        return $this->pollVotes()
            ->selectRaw('faction, option_id, COUNT(*) as cnt')
            ->groupBy('faction', 'option_id')
            ->get()
            ->groupBy('faction')
            ->map(fn ($rows) => $rows->pluck('cnt', 'option_id')->toArray())
            ->toArray();
    }
}
