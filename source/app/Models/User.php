<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FactionType;
use App\Enums\UserStatus;
use App\Enums\UserType;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\UserBadge;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'nickname',
        'avatar_url',
        'political_type',
        'test_score',
        'test_completed_at',
        'status',
        'email_verified_at',
        'email_verification_token',
        'manner_score',
        'title',
        'is_admin',
        'admin_role',
        'user_type',
        'suspended_until',
        'google2fa_secret',
        'google2fa_enabled',
        'level',
        'experience_points',
    ];

    protected $hidden = [
        'password',
        'email_verification_token',
        'remember_token',
        'google2fa_secret',
    ];

    protected function casts(): array
    {
        return [
            'political_type'      => FactionType::class,
            'status'              => UserStatus::class,
            'user_type'           => UserType::class,
            'test_score'          => 'integer',
            'manner_score'        => 'integer',
            'is_admin'            => 'boolean',
            'email_verified_at'   => 'datetime',
            'test_completed_at'   => 'datetime',
            'suspended_until'     => 'datetime',
            'google2fa_secret'    => 'encrypted',
            'google2fa_enabled'   => 'boolean',
            'level'               => 'integer',
            'experience_points'   => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // 관계 (Relationships)
    // -------------------------------------------------------------------------

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function politicalTestSessions(): HasMany
    {
        return $this->hasMany(PoliticalTestSession::class);
    }

    public function latestTestSession(): HasOne
    {
        return $this->hasOne(PoliticalTestSession::class)
            ->where('is_final', true)
            ->latestOfMany();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    // -------------------------------------------------------------------------
    // 헬퍼 메서드 (Helper Methods)
    // -------------------------------------------------------------------------

    /**
     * 성향 테스트를 완료했는지 여부.
     */
    public function hasCompletedPoliticalTest(): bool
    {
        return $this->political_type !== null && $this->test_completed_at !== null;
    }

    /**
     * 계정이 이용 가능한 상태인지 여부.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * 현재 일시 정지 상태인지 여부.
     */
    public function isSuspended(): bool
    {
        if ($this->status !== UserStatus::Suspended) {
            return false;
        }
        // 정지 기간이 종료된 경우
        if ($this->suspended_until !== null && $this->suspended_until->isPast()) {
            return false;
        }
        return true;
    }

    /**
     * 관리자 여부 확인.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * 특정 게시판에 접근 가능한지 확인.
     */
    public function canAccessBoard(Board $board): bool
    {
        // 아지트가 아닌 경우 모두 접근 가능
        if ($board->allowed_faction === 'all') {
            return true;
        }
        // 성향 테스트 미완료자는 아지트 접근 불가
        if (! $this->hasCompletedPoliticalTest()) {
            return false;
        }
        return $this->political_type?->value === $board->allowed_faction;
    }

    /**
     * 진영 이모지 반환.
     */
    public function factionEmoji(): string
    {
        return $this->political_type?->emoji() ?? '⚪';
    }
}
