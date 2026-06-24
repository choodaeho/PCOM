<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * users 테이블에 user_type 컬럼 추가.
     *
     * admin  : 관리자 계정
     * test   : 테스트/더미 계정 (개발·QA 전용)
     * normal : 일반 가입 회원 (기본값)
     *
     * 기존 레코드는 모두 'normal' 로 채워짐.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 20)
                ->default('normal')
                ->after('admin_role')
                ->comment('admin | test | normal');
        });

        // 기존 관리자(is_admin = true) 계정은 자동으로 admin 타입으로 업데이트
        DB::statement("UPDATE users SET user_type = 'admin' WHERE is_admin = true");

        // 인덱스: 관리자 콘솔에서 타입별 필터링 시 사용
        DB::statement('CREATE INDEX idx_users_user_type ON users (user_type) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
};
