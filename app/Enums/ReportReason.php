<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportReason: string
{
    case HateSpeech    = 'hate_speech';    // 혐오 발언
    case Misinformation = 'misinformation'; // 허위 정보
    case Spam          = 'spam';           // 스팸/광고
    case Obscene       = 'obscene';        // 음란물
    case Other         = 'other';          // 기타

    public function label(): string
    {
        return match($this) {
            self::HateSpeech     => '혐오 발언',
            self::Misinformation => '허위 정보',
            self::Spam           => '스팸/광고',
            self::Obscene        => '음란물',
            self::Other          => '기타',
        };
    }

    /**
     * 기타 사유 상세 입력이 필요한지 여부.
     */
    public function requiresDetail(): bool
    {
        return $this === self::Other;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
