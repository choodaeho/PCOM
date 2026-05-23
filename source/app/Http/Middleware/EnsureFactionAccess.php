<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Board;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 진영별 게시판 접근 제어 미들웨어.
 *
 * 라우트 파라미터 {board} 또는 {boardSlug}를 기준으로
 * 현재 로그인한 사용자의 진영과 게시판의 allowed_faction을 비교한다.
 *
 * 사용 예:
 *   Route::middleware(['auth', 'political.test', 'faction.access'])
 *        ->group(fn () => Route::get('/boards/{board}/posts', ...));
 */
class EnsureFactionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        // 관리자는 모든 게시판 접근 허용
        if ($user->isAdmin()) {
            return $next($request);
        }

        // 라우트에서 Board 모델 바인딩 시도
        $board = $request->route('board');

        // Board 인스턴스가 아닌 경우 (slug로 전달된 경우 직접 조회)
        if (! $board instanceof Board) {
            $slug  = $request->route('boardSlug') ?? $board;
            $board = Board::where('slug', $slug)->firstOrFail();
        }

        if (! $board->isAccessibleBy($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '해당 진영만 접근할 수 있는 공간입니다.',
                    'allowed_faction' => $board->allowed_faction,
                    'your_faction'    => $user->political_type?->value,
                ], Response::HTTP_FORBIDDEN);
            }

            $factionLabel = match($board->allowed_faction) {
                'conservative' => '보수',
                'moderate'     => '중도',
                'progressive'  => '진보',
                default        => '해당',
            };

            return redirect()
                ->back()
                ->with('error', "{$factionLabel} 진영만 입장할 수 있는 아지트입니다. 🔒");
        }

        return $next($request);
    }
}
