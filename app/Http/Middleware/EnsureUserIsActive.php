<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 사용자 계정 활성 상태 확인 미들웨어.
 *
 * 정지(suspended) 또는 차단(banned) 사용자의 접근을 차단.
 * 로그인 직후 및 민감한 액션 전에 적용.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // 이메일 미인증
        if ($user->status === UserStatus::Pending) {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '이메일 인증이 완료되지 않은 계정입니다.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->route('verification.notice')
                ->with('warning', '이메일 인증 후 이용하실 수 있습니다.');
        }

        // 일시 정지 — 정지 기간이 남아있는 경우만
        if ($user->isSuspended()) {
            Auth::logout();

            $until = $user->suspended_until?->format('Y년 m월 d일 H시');

            if ($request->expectsJson()) {
                return response()->json([
                    'message'       => '계정이 일시 정지 상태입니다.',
                    'suspended_until' => $user->suspended_until?->toIso8601String(),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('login')
                ->with('error', "계정이 {$until}까지 일시 정지되었습니다.");
        }

        // 영구 차단
        if ($user->status === UserStatus::Banned) {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '영구 차단된 계정입니다. 고객센터에 문의해 주세요.',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('login')
                ->with('error', '영구 차단된 계정입니다. 고객센터에 문의해 주세요.');
        }

        return $next($request);
    }
}
