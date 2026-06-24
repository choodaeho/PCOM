<?php

declare(strict_types=1);

namespace App\Enums;

enum BoardType: string
{
    case Azit       = 'azit';       // 진영 전용 아지트
    case Battle     = 'battle';     // 전 진영 전쟁터
    case Playground = 'playground'; // 정치 무관 자유 놀이터
    case Notice     = 'notice';     // 공지사항 (관리자 전용 작성)

    public function label(): string
    {
        return match($this) {
            self::Azit       => '아지트',
            self::Battle     => '전쟁터',
            self::Playground => '놀이터',
            self::Notice     => '공지사항',
        };
    }

    /**
     * 이 게시판 유형이 진영 제한을 적용하는지 여부.
     */
    public function isFactionRestricted(): bool
    {
        return $this === self::Azit;
    }

    /**
     * 진영 무관 자유 게시판인지 여부.
     */
    public function isPlayground(): bool
    {
        return $this === self::Playground;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
