<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\FactionType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * 회원가입 폼 표시.
     *
     * ?faction=conservative|moderate|progressive 쿼리가 있으면
     * (성향 테스트 결과 적용 후 복귀한 경우) 해당 성향을 미리 선택한다.
     */
    public function showForm(Request $request): Response
    {
        return Inertia::render('Auth/Register', [
            'preselectedFaction' => $request->query('faction'),
        ]);
    }

    /**
     * 회원가입 처리.
     *
     * 성향(political_type)은 반드시 폼에서 선택해야 한다.
     * 회원가입 시 성향을 직접 선택한 경우 test_score = null.
     * test_completed_at은 가입 시점으로 설정하여 커뮤니티 바로 이용 가능.
     */
    public function register(Request $request): mixed
    {
        $validated = $request->validate([
            'nickname'       => ['required', 'string', 'min:2', 'max:20', 'unique:users', 'regex:/^[가-힣a-zA-Z0-9_]+$/u'],
            'email'          => ['required', 'email:rfc,dns', 'unique:users'],
            'password'       => ['required', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'political_type' => ['required', 'string', Rule::in(FactionType::values())],
        ]);

        $user = User::create([
            'nickname'          => $validated['nickname'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'status'            => UserStatus::Pending,
            'political_type'    => $validated['political_type'],
            'test_score'        => null,   // 직접 선택이므로 점수 없음
            'test_completed_at' => now(), // 즉시 커뮤니티 이용 가능
        ]);

        // 게스트 테스트 세션 데이터 정리
        $request->session()->forget(['political_test_guest_result', 'political_test_source']);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
