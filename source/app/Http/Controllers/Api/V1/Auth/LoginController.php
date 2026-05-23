<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * POST /api/v1/auth/login
     *
     * 이메일/비밀번호 로그인.
     * Sanctum Personal Access Token 발급.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['이메일 또는 비밀번호가 올바르지 않습니다.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // 계정 상태 검사
        if (! $user->isActive()) {
            Auth::logout();
            return response()->json([
                'message' => match($user->status->value) {
                    'pending'   => '이메일 인증이 필요합니다.',
                    'suspended' => "계정이 {$user->suspended_until?->format('Y-m-d')}까지 정지되었습니다.",
                    'banned'    => '영구 차단된 계정입니다.',
                    default     => '이용이 제한된 계정입니다.',
                },
                'status' => $user->status->value,
            ], 403);
        }

        // 기존 토큰 삭제 후 새 토큰 발급
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'                 => $user->id,
                'email'              => $user->email,
                'nickname'           => $user->nickname,
                'political_type'     => $user->political_type?->value,
                'faction_emoji'      => $user->factionEmoji(),
                'is_admin'           => $user->is_admin,
                'test_completed'     => $user->hasCompletedPoliticalTest(),
                'email_verified'     => $user->email_verified_at !== null,
            ],
        ]);
    }
}
