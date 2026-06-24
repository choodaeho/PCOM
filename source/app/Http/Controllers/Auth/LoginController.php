<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): mixed
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            // 관리자 계정은 일반 로그인 페이지 사용 불가 → 관리자 전용 페이지로 안내
            if ($user->is_admin) {
                Auth::logout();
                return back()->withErrors([
                    'email' => '관리자 계정은 관리자 전용 로그인 페이지를 이용해주세요.',
                ])->with('admin_redirect', true);
            }

            if (!$user->isActive()) {
                Auth::logout();
                return back()->withErrors(['email' => '계정이 정지되었거나 비활성 상태입니다.']);
            }

            if (!$user->test_completed_at) {
                return redirect()->route('political-test.show');
            }

            return redirect()->intended(route('boards.index'));
        }

        return back()->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.']);
    }
}
