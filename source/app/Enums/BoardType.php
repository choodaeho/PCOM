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

    /**
     * 게시판 유형별 인기글 UI 명칭.
     * - 전쟁터: 화제글 (정치 커뮤니티 용어)
     * - 아지트: 베스트 (진영 내 추천글)
     * - 놀이터/공지: 인기글
     */
    public function hotLabel(): string
    {
        return match($this) {
            self::Battle     => '화제글',
            self::Azit       => '베스트',
            self::Playground => '인기글',
            self::Notice     => '인기글',
        };
    }

    /**
     * 인기글 자동 등재 추천 기준.
     * 이 수 이상 추천을 받으면 is_hot = true 자동 설정.
     * - 전쟁터: 10표 (정치 토론 특성상 높게 설정)
     * - 아지트/놀이터/공지: 5표
     */
    public function hotThreshold(): int
    {
        return match($this) {
            self::Battle     => 10,
            self::Azit       => 5,
            self::Playground => 5,
            self::Notice     => 5,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
