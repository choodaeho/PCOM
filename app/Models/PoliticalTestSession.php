<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoliticalTestSession extends Model
{
    protected $fillable = [
        'user_id',
        'answers',
        'total_score',
        'result_type',
        'is_final',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers'      => 'array',        // JSONB → PHP array
            'result_type'  => FactionType::class,
            'total_score'  => 'integer',
            'is_final'     => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // 관계
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    /**
     * 최종 유효 결과만 조회.
     */
    public function scopeFinal(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_final', true);
    }
}
