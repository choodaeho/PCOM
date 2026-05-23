<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminActionLog extends Model
{
    /**
     * 감사 로그는 수정·삭제 불가.
     * updated_at 컬럼 없음.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'action_type',
        'target_type',
        'target_id',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload'    => 'array',   // JSONB → PHP array
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // 관계
    // -------------------------------------------------------------------------

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** 대상 엔티티 (User | Post | Comment | Board 등) */
    public function target(): MorphTo
    {
        return $this->morphTo('target');
    }

    // -------------------------------------------------------------------------
    // 팩토리 메서드
    // -------------------------------------------------------------------------

    /**
     * 관리자 액션 로그를 간편하게 기록.
     *
     * @param  array<string, mixed>|null $payload
     */
    public static function record(
        int    $adminId,
        string $actionType,
        ?Model $target = null,
        ?array $payload = null,
    ): self {
        return static::create([
            'admin_id'    => $adminId,
            'action_type' => $actionType,
            'target_type' => $target ? get_class($target) : null,
            'target_id'   => $target?->getKey(),
            'payload'     => $payload,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    public function scopeForAdmin(
        \Illuminate\Database\Eloquent\Builder $query,
        int $adminId
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByActionType(
        \Illuminate\Database\Eloquent\Builder $query,
        string $actionType
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where('action_type', $actionType);
    }
}
