<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 관리자 패널 통합 인증 미들웨어.
 *
 * 다음 세 가지를 순서대로 검사합니다:
 *   1. 로그인 여부      → 미로그인 시 /admin/login 리다이렉트
 *   2. 관리자 권한 여부 → 비관리자 계정은 403 반환
 *   3. 2FA 인증 완료 여부 → 세션 'admin_2fa_verified' 미존재 시 /admin/login/2fa 리다이렉트
 *
 * 라우트 별칭: 'admin.auth'
 */
class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 로그인 확인
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        // 2. 관리자 권한 확인
        if (! $user->is_admin) {
            abort(403, '관리자 권한이 필요합니다.');
        }

        // 3. 2FA 인증 완료 확인
        if (! $request->session()->get('admin_2fa_verified')) {
            return redirect()->route('admin.login.2fa');
        }

        return $next($request);
    }
}
