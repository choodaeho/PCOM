<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 관리자 2단계 인증(Google Authenticator TOTP) 컬럼 추가.
 *
 * - google2fa_secret : TOTP 시크릿 키 (Laravel 암호화 저장)
 * - google2fa_enabled: 2FA 활성화 여부 (최초 설정 후 true)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google2fa_secret')->nullable()
                  ->after('admin_role')
                  ->comment('Google Authenticator TOTP 시크릿 (암호화 저장)');

            $table->boolean('google2fa_enabled')->default(false)
                  ->after('google2fa_secret')
                  ->comment('Google 2FA 활성화 여부 (최초 OTP 등록 후 true)');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google2fa_secret', 'google2fa_enabled']);
        });
    }
};
