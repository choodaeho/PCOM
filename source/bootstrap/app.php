<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\EnsureFactionAccess;
use App\Http\Middleware\EnsurePoliticalTestCompleted;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // -----------------------------------------------------------------
        // 미들웨어 별칭 등록
        // -----------------------------------------------------------------
        $middleware->alias([
            // 계정 활성 상태 확인 (정지/차단 검사)
            'user.active'    => EnsureUserIsActive::class,

            // 성향 테스트 완료 여부 확인
            'political.test' => EnsurePoliticalTestCompleted::class,

            // 진영별 게시판 접근 제한
            'faction.access' => EnsureFactionAccess::class,

            // 관리자 전용 접근 (is_admin 단순 확인 — 레거시 호환용)
            'admin'          => EnsureUserIsAdmin::class,

            // 관리자 패널 통합 인증 (로그인 + is_admin + 2FA 세션 모두 확인)
            'admin.auth'     => EnsureAdminAuthenticated::class,
        ]);

        // -----------------------------------------------------------------
        // API 전역 미들웨어
        // -----------------------------------------------------------------
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // -----------------------------------------------------------------
        // Web 전역 미들웨어
        // -----------------------------------------------------------------
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
