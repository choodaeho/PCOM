<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliticalTest extends Model
{
    protected $fillable = [
        'question',
        'options',
        'weight',
        'category',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options'    => 'array',  // JSONB → PHP array 자동 변환
            'weight'     => 'float',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    /**
     * 활성화된 문항만 조회.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 보수 성향 문항인지 여부 (weight 양수).
     */
    public function isConservativeBiased(): bool
    {
        return $this->weight > 0;
    }

    /**
     * 진보 성향 문항인지 여부 (weight 음수).
     */
    public function isProgressiveBiased(): bool
    {
        return $this->weight < 0;
    }
}
