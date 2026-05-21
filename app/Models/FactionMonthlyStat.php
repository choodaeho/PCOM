<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FactionType;
use Illuminate\Database\Eloquent\Model;

class FactionMonthlyStat extends Model
{
    protected $table = 'factions_monthly_stats';

    protected $fillable = [
        'faction_type',
        'stat_year_month',
        'post_count',
        'comment_count',
        'vote_up_count',
        'vote_down_count',
        'report_count',
        'avg_active_user_count',
        'peak_active_user_count',
        'total_raw_score',
        'avg_normalized_score',
        'best_rank_in_month',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'faction_type'           => FactionType::class,
            'post_count'             => 'integer',
            'comment_count'          => 'integer',
            'vote_up_count'          => 'integer',
            'vote_down_count'        => 'integer',
            'report_count'           => 'integer',
            'avg_active_user_count'  => 'float',
            'peak_active_user_count' => 'integer',
            'total_raw_score'        => 'float',
            'avg_normalized_score'   => 'float',
            'best_rank_in_month'     => 'integer',
            'calculated_at'          => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    public function scopeForYear(
        \Illuminate\Database\Eloquent\Builder $query,
        int $year
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('stat_year_month', 'like', "{$year}-%");
    }

    public function scopeForFaction(
        \Illuminate\Database\Eloquent\Builder $query,
        FactionType $faction
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('faction_type', $faction->value);
    }
}
