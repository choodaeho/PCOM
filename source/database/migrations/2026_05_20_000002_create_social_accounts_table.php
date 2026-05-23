<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('provider', 20)->comment('kakao | naver | google');
            $table->string('provider_id', 191)->comment('소셜 플랫폼의 고유 사용자 ID');
            $table->string('provider_email', 191)->nullable()->comment('소셜에서 제공하는 이메일 (변경될 수 있음)');
            $table->string('access_token', 1000)->nullable();
            $table->string('refresh_token', 1000)->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->timestamps();

            // 동일 소셜 계정 중복 가입 방지
            $table->unique(['provider', 'provider_id']);
        });

        // 인덱스
        \DB::statement('CREATE INDEX idx_social_accounts_user_id ON social_accounts (user_id)');
        \DB::statement('CREATE INDEX idx_social_accounts_provider ON social_accounts (provider, provider_email)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
