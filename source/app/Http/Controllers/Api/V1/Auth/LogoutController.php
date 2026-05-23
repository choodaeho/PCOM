<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * POST /api/v1/auth/logout
     *
     * 현재 사용자의 Sanctum 토큰을 폐기하고 로그아웃 처리.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // 현재 요청에 사용된 토큰만 삭제
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => '로그아웃되었습니다.',
        ]);
    }
}
