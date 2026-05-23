<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FactionType;
use Illuminate\Database\Eloquent\Model;

class FactionYearlyStat extends Model
{
    protected $table = 'factions_yearly_stats';

    protected $fillable = [
        'faction_type',
        'stat_year',
        'post_count',
        'comment_count',
        'vote_up_count',
        'vote_down_count',
        'report_count',
        'avg_active_user_count',
        'peak_active_user_count',
        'total_raw_score',
        'avg_normalized_score',
        'months_as_top_faction',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'faction_type'           => FactionType::class,
            'stat_year'              => 'integer',
            'post_count'             => 'integer',
            'comment_count'          => 'integer',
            'vote_up_count'          => 'integer',
            'vote_down_count'        => 'integer',
            'report_count'           => 'integer',
            'avg_active_user_count'  => 'float',
            'peak_active_user_count' => 'integer',
            'total_raw_score'        => 'float',
            'avg_normalized_score'   => 'float',
            'months_as_top_faction'  => 'integer',
            'calculated_at'          => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 특정 연도의 연간 챔피언 진영 반환.
     */
    public static function championOf(int $year): ?self
    {
        return static::where('stat_year', $year)
            ->orderBy('avg_normalized_score', 'desc')
            ->first();
    }
}
