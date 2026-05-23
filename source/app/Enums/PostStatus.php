<?php

declare(strict_types=1);

namespace App\Enums;

enum PostStatus: string
{
    case Published       = 'published';        // 공개 게시 중
    case Hidden          = 'hidden';           // 숨김 (사용자 직접 비공개)
    case DeletedByAdmin  = 'deleted_by_admin'; // 관리자 삭제

    public function label(): string
    {
        return match($this) {
            self::Published      => '게시 중',
            self::Hidden         => '숨김',
            self::DeletedByAdmin => '관리자 삭제',
        };
    }

    /**
     * 일반 사용자에게 표시 가능한 상태인지 여부.
     */
    public function isVisible(): bool
    {
        return $this === self::Published;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
