<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FactionType;
use Illuminate\Database\Eloquent\Model;

class FactionDailyStat extends Model
{
    protected $table = 'factions_daily_stats';

    protected $fillable = [
        'faction_type',
        'stat_date',
        'post_count',
        'comment_count',
        'vote_up_count',
        'vote_down_count',
        'report_count',
        'active_user_count',
        'new_user_count',
        'raw_score',
        'normalized_score',
        'rank',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'faction_type'      => FactionType::class,
            'stat_date'         => 'date',
            'post_count'        => 'integer',
            'comment_count'     => 'integer',
            'vote_up_count'     => 'integer',
            'vote_down_count'   => 'integer',
            'report_count'      => 'integer',
            'active_user_count' => 'integer',
            'new_user_count'    => 'integer',
            'raw_score'         => 'float',
            'normalized_score'  => 'float',
            'rank'              => 'integer',
            'calculated_at'     => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    public function scopeForFaction(
        \Illuminate\Database\Eloquent\Builder $query,
        FactionType $faction
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('faction_type', $faction->value);
    }

    public function scopeForPeriod(
        \Illuminate\Database\Eloquent\Builder $query,
        string $from,
        string $to
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereBetween('stat_date', [$from, $to]);
    }

    public function scopeLatest30Days(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('stat_date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('stat_date', 'desc');
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 오늘의 세 진영 점수를 한 번에 조회.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function todayStats(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('stat_date', now()->toDateString())
            ->orderBy('normalized_score', 'desc')
            ->get();
    }
}
