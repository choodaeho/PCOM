<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

/**
 * 관리자 전용 2단계 인증 로그인 컨트롤러.
 *
 * 로그인 플로우:
 *   1단계 POST /admin/login       → 이메일 + 비밀번호 검증
 *   2단계 GET  /admin/login/2fa   → OTP 입력 폼 표시 (최초 설정 시 QR 코드 표시)
 *   2단계 POST /admin/login/2fa   → OTP 검증 후 Auth::loginUsingId() 완료
 *
 * 세션 키:
 *   admin_2fa_pending_id    : 1단계 통과 후 로그인 대기 중인 관리자 User ID
 *   admin_2fa_setup_secret  : 최초 2FA 설정 시 생성한 임시 TOTP 시크릿
 *   admin_2fa_verified      : 2단계 인증 완료 여부 (관리자 패널 접근 인가)
 */
class AdminLoginController extends Controller
{
    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    // ─────────────────────────────────────────────
    // 1단계: 이메일 + 비밀번호
    // ─────────────────────────────────────────────

    /** GET /admin/login */
    public function showForm(Request $request): Response|RedirectResponse
    {
        // 이미 2FA까지 완료된 관리자라면 대시보드로
        if (Auth::check() && Auth::user()->is_admin && $request->session()->get('admin_2fa_verified')) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Auth/Login');
    }

    /** POST /admin/login */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $validated['email'])
            ->where('is_admin', true)
            ->first();

        // 계정 없음 또는 비밀번호 불일치 (관리자 계정인지 노출하지 않음)
        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.']);
        }

        if (! $user->isActive()) {
            return back()->withErrors(['email' => '계정이 비활성 상태입니다. 시스템 관리자에게 문의하세요.']);
        }

        // 1단계 통과 → 세션에 대기 ID 저장, 2FA 페이지로 이동 (아직 로그인 완료 X)
        $request->session()->put('admin_2fa_pending_id', $user->id);
        $request->session()->forget(['admin_2fa_verified', 'admin_2fa_setup_secret']);

        return redirect()->route('admin.login.2fa');
    }

    // ─────────────────────────────────────────────
    // 2단계: Google Authenticator OTP
    // ─────────────────────────────────────────────

    /** GET /admin/login/2fa */
    public function show2fa(Request $request): Response|RedirectResponse
    {
        $pendingId = $request->session()->get('admin_2fa_pending_id');

        if (! $pendingId) {
            return redirect()->route('admin.login');
        }

        /** @var User $user */
        $user = User::findOrFail($pendingId);

        // ── 최초 설정 모드 ────────────────────────────────────────────────
        if (! $user->google2fa_enabled) {
            // 세션에 이미 시크릿이 있으면 재사용 (페이지 새로고침 대응)
            $secret = $request->session()->get('admin_2fa_setup_secret')
                ?? $this->google2fa->generateSecretKey();

            $request->session()->put('admin_2fa_setup_secret', $secret);

            $otpauthUrl = $this->google2fa->getQRCodeUrl(
                company: 'Polit 관리자',
                holder:  $user->email,
                secret:  $secret,
            );

            return Inertia::render('Admin/Auth/TwoFactor', [
                'mode'       => 'setup',
                'otpauthUrl' => $otpauthUrl,
                'secret'     => $secret,
                'email'      => $user->email,
            ]);
        }

        // ── 일반 인증 모드 ────────────────────────────────────────────────
        return Inertia::render('Admin/Auth/TwoFactor', [
            'mode'  => 'verify',
            'email' => $user->email,
        ]);
    }

    /** POST /admin/login/2fa */
    public function verify2fa(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pendingId = $request->session()->get('admin_2fa_pending_id');

        if (! $pendingId) {
            return redirect()->route('admin.login')
                ->withErrors(['otp' => '세션이 만료되었습니다. 다시 로그인해주세요.']);
        }

        /** @var User $user */
        $user = User::findOrFail($pendingId);

        // ── 최초 설정: 세션 시크릿으로 검증 후 저장 ──────────────────────
        if (! $user->google2fa_enabled) {
            $secret = $request->session()->get('admin_2fa_setup_secret');

            if (! $secret) {
                return redirect()->route('admin.login.2fa')
                    ->withErrors(['otp' => 'QR 코드 세션이 만료되었습니다. 다시 시도해주세요.']);
            }

            // window=1 → ±30초 허용 (시계 오차 대응)
            if (! $this->google2fa->verifyKey($secret, $request->string('otp'), 1)) {
                return back()->withErrors(['otp' => 'OTP 코드가 올바르지 않습니다. Google Authenticator를 확인해주세요.']);
            }

            // 시크릿 저장 및 2FA 활성화 (google2fa_secret은 모델에서 'encrypted' cast 적용)
            $user->update([
                'google2fa_secret'  => $secret,
                'google2fa_enabled' => true,
            ]);

            $request->session()->forget('admin_2fa_setup_secret');
        } else {
            // ── 일반 인증: 저장된 시크릿으로 검증 ───────────────────────
            if (! $this->google2fa->verifyKey((string) $user->google2fa_secret, $request->string('otp'), 1)) {
                return back()->withErrors(['otp' => 'OTP 코드가 올바르지 않습니다. Google Authenticator를 확인해주세요.']);
            }
        }

        // 로그인 완료
        Auth::loginUsingId($user->id);
        $request->session()->regenerate();
        $request->session()->forget('admin_2fa_pending_id');
        $request->session()->put('admin_2fa_verified', true);

        return redirect()->route('admin.dashboard');
    }

    // ─────────────────────────────────────────────
    // 로그아웃
    // ─────────────────────────────────────────────

    /** POST /admin/logout */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->forget(['admin_2fa_verified', 'admin_2fa_pending_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
