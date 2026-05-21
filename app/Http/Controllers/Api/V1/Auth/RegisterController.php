<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * POST /api/v1/auth/register
     *
     * 이메일 회원가입.
     * 성공 시 인증 메일 발송 후 201 반환.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email:rfc,dns', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'nickname' => ['required', 'string', 'min:2', 'max:50', 'unique:users,nickname',
                           'regex:/^[가-힣a-zA-Z0-9_]+$/'],
        ]);

        $user = User::create([
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'nickname' => $validated['nickname'],
            'status'   => 'pending',
            'email_verification_token' => Str::random(64),
        ]);

        // 이메일 인증 메일 발송
        event(new Registered($user));

        return response()->json([
            'message' => '회원가입이 완료되었습니다. 이메일 인증 후 이용하실 수 있습니다.',
            'user'    => [
                'id'       => $user->id,
                'email'    => $user->email,
                'nickname' => $user->nickname,
            ],
        ], 201);
    }
}
