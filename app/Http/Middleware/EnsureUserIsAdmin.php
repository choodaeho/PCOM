<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 관리자 접근 제한 미들웨어.
 *
 * users.is_admin = true 인 계정만 통과.
 * 라우트 별칭: 'admin'
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => '관리자 권한이 필요합니다.'], 403);
            }
            abort(403, '관리자 권한이 필요합니다.');
        }

        return $next($request);
    }
}
