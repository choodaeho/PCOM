<?php

declare(strict_types=1);

namespace App\Enums;

enum VoteType: string
{
    case Up   = 'up';   // 추천
    case Down = 'down'; // 비추천

    public function label(): string
    {
        return match($this) {
            self::Up   => '추천',
            self::Down => '비추천',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
