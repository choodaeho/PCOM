<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VoteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Vote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'votable_id',
        'votable_type',
        'vote_type',
    ];

    protected function casts(): array
    {
        return [
            'vote_type'  => VoteType::class,
            'created_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // 관계
    // -------------------------------------------------------------------------

    /** Polymorphic 대상 (Post | Comment) */
    public function votable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // 헬퍼
    // -------------------------------------------------------------------------

    public function isUpVote(): bool
    {
        return $this->vote_type === VoteType::Up;
    }

    public function isDownVote(): bool
    {
        return $this->vote_type === VoteType::Down;
    }
}
