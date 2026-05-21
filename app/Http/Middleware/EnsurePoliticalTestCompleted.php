<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 성향 테스트 완료 여부 확인 미들웨어.
 *
 * 아지트/전쟁터 접근 전에 반드시 성향 테스트를 완료해야 함.
 * 미완료 시 성향 테스트 페이지로 리다이렉트.
 */
class EnsurePoliticalTestCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            // 비로그인 → 로그인 페이지로
            return redirect()->route('login');
        }

        if (! $user->hasCompletedPoliticalTest()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '성향 테스트를 완료해야 이용할 수 있습니다.',
                    'redirect' => route('political-test.show'),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()
                ->route('political-test.show')
                ->with('info', '커뮤니티 이용을 위해 성향 테스트를 먼저 완료해 주세요.');
        }

        return $next($request);
    }
}
