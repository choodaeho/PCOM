<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Pending   = 'pending';   // 이메일 미인증
    case Active    = 'active';    // 정상 활동
    case Suspended = 'suspended'; // 일시 정지
    case Banned    = 'banned';    // 영구 차단

    public function label(): string
    {
        return match($this) {
            self::Pending   => '인증 대기',
            self::Active    => '정상',
            self::Suspended => '일시 정지',
            self::Banned    => '영구 차단',
        };
    }

    /**
     * 플랫폼 이용이 가능한 상태인지 여부.
     */
    public function canAccess(): bool
    {
        return $this === self::Active;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
