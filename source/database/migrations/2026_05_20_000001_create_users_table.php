<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create ENUM types for PostgreSQL
        DB::statement("CREATE TYPE faction_type AS ENUM ('conservative', 'moderate', 'progressive')");
        DB::statement("CREATE TYPE user_status_type AS ENUM ('pending', 'active', 'suspended', 'banned')");

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->string('password', 255)->nullable()->comment('소셜 전용 회원은 null 가능');
            $table->string('nickname', 50)->unique();
            $table->string('avatar_url', 500)->nullable();

            // 정치 성향
            $table->string('political_type', 20)->nullable()->comment('conservative | moderate | progressive');
            $table->smallInteger('test_score')->nullable()->comment('성향 테스트 점수 (-100 ~ +100)');
            $table->timestamp('test_completed_at')->nullable()->comment('테스트 완료 시각');

            // 계정 상태
            $table->string('status', 20)->default('pending')->comment('pending | active | suspended | banned');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token', 100)->nullable();

            // 매너/활동 지표
            $table->unsignedSmallInteger('manner_score')->default(100)->comment('기본 100점, 신고 누적시 감점');
            $table->string('title', 50)->nullable()->comment('부여된 칭호 (진영 대변인 등)');

            // 관리자 플래그
            $table->boolean('is_admin')->default(false);
            $table->string('admin_role', 30)->nullable()->comment('super_admin | content_admin | user_admin | stats_admin');

            // 소프트 삭제
            $table->timestamp('suspended_until')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 인덱스
        DB::statement('CREATE INDEX idx_users_political_type ON users (political_type) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_users_status ON users (status) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_users_is_admin ON users (is_admin) WHERE is_admin = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        DB::statement('DROP TYPE IF EXISTS faction_type');
        DB::statement('DROP TYPE IF EXISTS user_status_type');
    }
};
