<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 회원 계정 유형.
 *
 * admin  : 운영/관리자 계정 (is_admin = true 과 함께 사용)
 * test   : 테스트용 더미 계정 (개발·QA 전용)
 * normal : 일반 가입 회원
 */
enum UserType: string
{
    case Admin  = 'admin';   // 관리자 계정
    case Test   = 'test';    // 테스트 계정
    case Normal = 'normal';  // 일반 회원

    public function label(): string
    {
        return match($this) {
            self::Admin  => '관리자',
            self::Test   => '테스트',
            self::Normal => '일반',
        };
    }

    /**
     * 관리자 계정 여부.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * 테스트 계정 여부 (관리 목적 필터링 시 사용).
     */
    public function isTest(): bool
    {
        return $this === self::Test;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
