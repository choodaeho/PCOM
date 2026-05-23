<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ScoreWeight extends Model
{
    protected $fillable = [
        'action_type',
        'weight',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weight'    => 'float',
            'is_active' => 'boolean',
        ];
    }

    /** Redis 캐시 키 */
    private const CACHE_KEY = 'score_weights:all';
    private const CACHE_TTL = 3600; // 1시간

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // 정적 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 활성화된 가중치를 action_type => weight 맵으로 반환.
     * Redis 캐시 적용 (관리자가 수정 시 캐시 무효화 필요).
     *
     * @return array<string, float>
     */
    public static function getCachedWeights(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::active()
                ->pluck('weight', 'action_type')
                ->toArray();
        });
    }

    /**
     * 가중치 캐시 무효화.
     * 관리자가 가중치 변경 시 호출.
     */
    public static function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
