<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'version',
        'title',
        'content',
        'effective_date',
        'is_current',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_current'     => 'boolean',
        'published_at'   => 'datetime',
    ];

    // ─────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────

    /** 특정 유형의 현재 적용 버전 */
    public function scopeCurrentOf(Builder $query, string $type): Builder
    {
        return $query->where('type', $type)->where('is_current', true);
    }

    /** 특정 유형의 전체 이력 (최신 시행일 먼저) */
    public function scopeHistoryOf(Builder $query, string $type): Builder
    {
        return $query->where('type', $type)->orderByDesc('effective_date');
    }
}
