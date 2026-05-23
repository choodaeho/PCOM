<?php

declare(strict_types=1);

namespace App\Enums;

enum BoardType: string
{
    case Azit   = 'azit';   // 진영 전용 아지트
    case Battle = 'battle'; // 전 진영 전쟁터
    case Notice = 'notice'; // 공지사항 (관리자 전용 작성)

    public function label(): string
    {
        return match($this) {
            self::Azit   => '아지트',
            self::Battle => '전쟁터',
            self::Notice => '공지사항',
        };
    }

    /**
     * 이 게시판 유형이 진영 제한을 적용하는지 여부.
     */
    public function isFactionRestricted(): bool
    {
        return $this === self::Azit;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
