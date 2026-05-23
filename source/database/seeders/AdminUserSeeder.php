<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FactionType;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * 최초 슈퍼관리자 계정 시드.
     *
     * ⚠️ 프로덕션에서는 반드시 환경변수로 비밀번호를 설정하거나
     *    시드 후 즉시 변경할 것.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@polit.kr');
        $adminPassword = env('ADMIN_PASSWORD', 'Admin1234!');

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'nickname'            => '폴릿관리자',
                'password'            => Hash::make($adminPassword),
                'political_type'      => FactionType::Moderate,
                'test_score'          => 0,
                'status'              => UserStatus::Active,
                'is_admin'            => true,
                'test_completed_at'   => now(),
                'email_verified_at'   => now(),
                'manner_score'        => 100,
            ]
        );

        $this->command->info("Admin user created: {$adminEmail}");
        $this->command->warn('⚠️  반드시 관리자 비밀번호를 변경하세요!');
    }
}
