<?php

declare(strict_types=1);

namespace App\Enums;

enum FactionType: string
{
    case Conservative = 'conservative'; // 보수
    case Moderate     = 'moderate';     // 중도
    case Progressive  = 'progressive';  // 진보

    /**
     * 한국어 레이블 반환.
     */
    public function label(): string
    {
        return match($this) {
            self::Conservative => '보수',
            self::Moderate     => '중도',
            self::Progressive  => '진보',
        };
    }

    /**
     * 진영 대표 색상 (HEX).
     */
    public function color(): string
    {
        return match($this) {
            self::Conservative => '#E24B4A', // 빨강 (국민의힘 계열)
            self::Moderate     => '#7F77DD', // 보라
            self::Progressive  => '#378ADD', // 파랑 (민주당 계열)
        };
    }

    /**
     * 진영 이모지 아이콘.
     */
    public function emoji(): string
    {
        return match($this) {
            self::Conservative => '🔴',
            self::Moderate     => '🟣',
            self::Progressive  => '🔵',
        };
    }

    /**
     * 성향 점수(-100~+100)로 진영 결정.
     *   양수(+25 이상) → 보수
     *   음수(-25 이하) → 진보
     *   중간           → 중도
     */
    public static function fromScore(int $score): self
    {
        return match(true) {
            $score >= 25  => self::Conservative,
            $score <= -25 => self::Progressive,
            default       => self::Moderate,
        };
    }

    /**
     * 모든 진영의 값 배열 반환.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
