<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    /**
     * GET /api/v1/auth/social/{provider}
     *
     * 소셜 로그인 리다이렉트 URL 반환.
     * SPA에서 이 URL로 팝업/리다이렉트 처리.
     */
    public function redirectUrl(string $provider): JsonResponse
    {
        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();

        return response()->json(['redirect_url' => $url]);
    }

    /**
     * POST /api/v1/auth/social/{provider}/callback
     *
     * 소셜 인가 코드를 서버에서 교환하여 사용자 생성/로그인.
     * Request body: { "code": "..." }
     */
    public function callback(Request $request, string $provider): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            return response()->json(['message' => '소셜 로그인에 실패했습니다. 다시 시도해 주세요.'], 422);
        }

        $user = DB::transaction(function () use ($provider, $socialUser) {
            // provider + provider_id로 기존 소셜 계정 조회
            $socialAccount = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($socialAccount !== null) {
                // 기존 소셜 계정 → 연결된 User 반환
                $socialAccount->update([
                    'access_token'     => $socialUser->token,
                    'refresh_token'    => $socialUser->refreshToken,
                    'token_expires_at' => $socialUser->expiresIn
                        ? now()->addSeconds($socialUser->expiresIn)
                        : null,
                ]);
                return $socialAccount->user;
            }

            // 동일 이메일의 기존 User가 있으면 소셜 계정만 연결
            $email = $socialUser->getEmail();
            $user  = $email ? User::where('email', $email)->first() : null;

            if ($user === null) {
                // 신규 사용자 생성
                $user = User::create([
                    'email'              => $email ?? "{$provider}_{$socialUser->getId()}@polit.local",
                    'nickname'           => $this->generateUniqueNickname($socialUser->getNickname() ?? $socialUser->getName()),
                    'avatar_url'         => $socialUser->getAvatar(),
                    'status'             => 'active',  // 소셜은 이메일 인증 불필요
                    'email_verified_at'  => now(),
                ]);
            }

            // 소셜 계정 연결
            SocialAccount::create([
                'user_id'          => $user->id,
                'provider'         => $provider,
                'provider_id'      => $socialUser->getId(),
                'provider_email'   => $email,
                'access_token'     => $socialUser->token,
                'refresh_token'    => $socialUser->refreshToken,
                'token_expires_at' => $socialUser->expiresIn
                    ? now()->addSeconds($socialUser->expiresIn)
                    : null,
            ]);

            return $user;
        });

        // 기존 토큰 삭제 후 새 토큰 발급
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'             => $user->id,
                'email'          => $user->email,
                'nickname'       => $user->nickname,
                'avatar_url'     => $user->avatar_url,
                'political_type' => $user->political_type?->value,
                'faction_emoji'  => $user->factionEmoji(),
                'is_admin'       => $user->is_admin,
                'test_completed' => $user->hasCompletedPoliticalTest(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // 내부 헬퍼
    // -------------------------------------------------------------------------

    /**
     * 중복되지 않는 닉네임 생성.
     * 소셜 제공자의 닉네임 + 랜덤 숫자 suffix.
     */
    private function generateUniqueNickname(string $base): string
    {
        $nickname = mb_substr(preg_replace('/[^가-힣a-zA-Z0-9_]/', '', $base), 0, 40);
        $nickname = $nickname ?: '폴릿회원';

        while (User::where('nickname', $nickname)->exists()) {
            $nickname = $base . Str::random(4);
        }

        return $nickname;
    }
}
