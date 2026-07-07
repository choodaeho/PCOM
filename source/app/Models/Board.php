<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BoardType;
use App\Enums\FactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'board_type',
        'allowed_faction',
        'sort_order',
        'is_active',
        'allow_anonymous',
        'post_count',
        'categories',
        'admin_memo',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'board_type'      => BoardType::class,
            'sort_order'      => 'integer',
            'post_count'      => 'integer',
            'is_active'       => 'boolean',
            'allow_anonymous' => 'boolean',
            'categories'      => 'array',   // JSONB → PHP array
        ];
    }

    // -------------------------------------------------------------------------
    // 관계
    // -------------------------------------------------------------------------

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------------------------------
    // 스코프
    // -------------------------------------------------------------------------

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * 특정 진영이 접근 가능한 게시판만 필터링.
     */
    public function scopeAccessibleByFaction(
        \Illuminate\Database\Eloquent\Builder $query,
        FactionType $faction
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->where(function ($q) use ($faction) {
            $q->where('allowed_faction', 'all')
              ->orWhere('allowed_faction', $faction->value);
        });
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 아지트 게시판인지 여부.
     */
    public function isAzit(): bool
    {
        return $this->board_type === BoardType::Azit;
    }

    /**
     * 전쟁터 게시판인지 여부.
     */
    public function isBattle(): bool
    {
        return $this->board_type === BoardType::Battle;
    }

    /**
     * 놀이터 게시판인지 여부.
     */
    public function isPlayground(): bool
    {
        return $this->board_type === BoardType::Playground;
    }

    /**
     * 특정 사용자가 이 게시판에 접근 가능한지 확인.
     */
    public function isAccessibleBy(User $user): bool
    {
        if ($this->allowed_faction === 'all') {
            return true;
        }
        return $user->political_type?->value === $this->allowed_faction;
    }

    /**
     * 표시용 게시판 이름 (아이콘 포함).
     */
    public function displayName(): string
    {
        return $this->icon ? "{$this->icon} {$this->name}" : $this->name;
    }
}
