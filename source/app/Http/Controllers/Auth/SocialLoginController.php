<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['social' => '소셜 로그인에 실패했습니다.']);
        }

        $user = DB::transaction(function () use ($provider, $socialUser) {
            $account = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->with('user')
                ->first();

            if ($account) {
                $account->update([
                    'access_token'     => $socialUser->token,
                    'refresh_token'    => $socialUser->refreshToken,
                    'token_expires_at' => $socialUser->expiresIn
                        ? now()->addSeconds($socialUser->expiresIn) : null,
                ]);

                return $account->user;
            }

            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'nickname'          => $this->generateNickname($socialUser->getNickname() ?? $socialUser->getName()),
                    'email'             => $socialUser->getEmail(),
                    'password'          => bcrypt(Str::random(32)),
                    'status'            => UserStatus::Active,
                    'email_verified_at' => now(),
                ]);
            }

            SocialAccount::create([
                'user_id'          => $user->id,
                'provider'         => $provider,
                'provider_id'      => $socialUser->getId(),
                'access_token'     => $socialUser->token,
                'refresh_token'    => $socialUser->refreshToken,
                'token_expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
            ]);

            return $user;
        });

        Auth::login($user, true);

        if (!$user->test_completed_at) {
            return redirect()->route('political-test.show');
        }

        return redirect()->route('boards.index');
    }

    private function generateNickname(string $base): string
    {
        $base     = Str::limit(preg_replace('/[^가-힣a-zA-Z0-9_]/u', '', $base), 15, '');
        $nickname = $base ?: '폴릿유저';
        $i        = 1;

        while (User::where('nickname', $nickname)->exists()) {
            $nickname = $base . $i++;
        }

        return $nickname;
    }
}
