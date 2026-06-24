<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FactionType;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * 슈퍼관리자 계정 시드.
     *
     * 계정 정보:
     *   nickname : 대한민국
     *   email    : hhpapa77@polit.kr
     *   password : (env ADMIN_PASSWORD 또는 기본값)
     *
     * ⚠️  비밀번호는 .env ADMIN_PASSWORD 환경변수로 주입하거나
     *     시드 후 즉시 관리자 패널에서 변경할 것.
     */
    public function run(): void
    {
        $adminEmail    = env('ADMIN_EMAIL', 'hhpapa77@polit.kr');
        $adminPassword = env('ADMIN_PASSWORD', 'itweb8335#');

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'nickname'          => '대한민국',
                'password'          => Hash::make($adminPassword),
                'political_type'    => FactionType::Moderate,
                'test_score'        => 0,
                'status'            => UserStatus::Active,
                'user_type'         => UserType::Admin,
                'is_admin'          => true,
                'admin_role'        => 'super_admin',
                'test_completed_at' => now(),
                'email_verified_at' => now(),
                'manner_score'      => 100,
            ]
        );

        $this->command->info("✅ 관리자 계정 생성 완료: {$adminEmail}");
        $this->command->line("   nickname : 대한민국");
        $this->command->line("   password : (ADMIN_PASSWORD env or default)");
        $this->command->warn('⚠️  운영 배포 전 반드시 관리자 비밀번호를 변경하세요!');
    }
}
