<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialLoginController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\BoardController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\VoteController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\PollController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\PoliticalTestController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\BoardController as AdminBoardController;
use App\Http\Controllers\Api\V1\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\V1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\V1\Admin\PollController as AdminPollController;
use App\Http\Controllers\Api\V1\Admin\ScoreWeightController as AdminScoreWeightController;
use App\Http\Controllers\Api\V1\Admin\StatsController as AdminStatsController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════════════════════
// API v1
// 모든 응답: JSON  |  인증: Laravel Sanctum (SPA Cookie or Bearer Token)
// ═══════════════════════════════════════════════════════════════════════════════

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ─────────────────────────────────────────────────────────────────────────
    // [PUBLIC] 인증 불필요 엔드포인트
    // ─────────────────────────────────────────────────────────────────────────

    Route::prefix('auth')->name('auth.')->group(function () {
        // 이메일 회원가입
        Route::post('/register', [RegisterController::class, 'register'])
            ->middleware('throttle:10,1')
            ->name('register');

        // 이메일 로그인
        Route::post('/login', [LoginController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login');

        // 소셜 로그인 리다이렉트 URL 반환
        Route::get('/social/{provider}', [SocialLoginController::class, 'redirectUrl'])
            ->whereIn('provider', ['kakao', 'naver', 'google'])
            ->name('social.redirect');

        // 소셜 콜백 (code 교환 → 사용자 생성/로그인)
        Route::post('/social/{provider}/callback', [SocialLoginController::class, 'callback'])
            ->whereIn('provider', ['kakao', 'naver', 'google'])
            ->name('social.callback');

        // 비밀번호 재설정
        Route::post('/password/forgot', [PasswordResetController::class, 'sendLink'])
            ->middleware('throttle:5,1')
            ->name('password.forgot');

        Route::post('/password/reset', [PasswordResetController::class, 'reset'])
            ->middleware('throttle:5,1')
            ->name('password.reset');
    });

    // 실시간 진영 점수 (헤더 위젯용, 비로그인도 열람 가능)
    Route::get('/stats/realtime', [StatsController::class, 'realtime'])
        ->middleware('throttle:60,1')
        ->name('stats.realtime');

    // ─────────────────────────────────────────────────────────────────────────
    // [AUTH] 로그인 필수
    // ─────────────────────────────────────────────────────────────────────────

    Route::middleware(['auth:sanctum', 'user.active'])->group(function () {

        // 로그아웃
        Route::post('/auth/logout', LogoutController::class)->name('auth.logout');

        // 내 정보 조회
        Route::get('/auth/me', [UserController::class, 'me'])->name('auth.me');

        // 이메일 인증
        Route::prefix('auth/email')->name('auth.email.')->group(function () {
            Route::get('/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verify');

            Route::post('/resend', [EmailVerificationController::class, 'resend'])
                ->middleware('throttle:6,1')
                ->name('resend');
        });

        // ─────────────────────────────────────────────
        // 성향 테스트 (이메일 인증 후, 테스트 전 접근)
        // ─────────────────────────────────────────────
        Route::prefix('political-test')->name('political-test.')->group(function () {
            Route::get('/questions', [PoliticalTestController::class, 'questions'])
                ->name('questions');

            Route::post('/submit', [PoliticalTestController::class, 'submit'])
                ->middleware('throttle:5,10')
                ->name('submit');

            Route::get('/result', [PoliticalTestController::class, 'result'])
                ->name('result');
        });

        // ─────────────────────────────────────────────
        // 커뮤니티 (성향 테스트 완료 필수)
        // ─────────────────────────────────────────────
        Route::middleware(['verified', 'political.test'])->group(function () {

            // ── 게시판 ──────────────────────────────────
            Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');
            Route::get('/boards/{board:slug}', [BoardController::class, 'show'])
                ->middleware('faction.access')
                ->name('boards.show');

            // ── 게시글 ──────────────────────────────────
            // 목록 (게시판별)
            Route::get('/boards/{board:slug}/posts', [PostController::class, 'index'])
                ->middleware('faction.access')
                ->name('posts.index');

            // 작성
            Route::post('/boards/{board:slug}/posts', [PostController::class, 'store'])
                ->middleware(['faction.access', 'throttle:30,1'])
                ->name('posts.store');

            // 단건 조회 (board 컨텍스트 불필요 — post_id로 직접 접근)
            Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

            // 수정/삭제 (본인 소유 확인은 Controller Policy로)
            Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
            Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');

            // ── 댓글 ────────────────────────────────────
            Route::get('/posts/{post}/comments', [CommentController::class, 'index'])
                ->name('comments.index');

            Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
                ->middleware('throttle:60,1')
                ->name('comments.store');

            Route::put('/comments/{comment}', [CommentController::class, 'update'])
                ->name('comments.update');

            Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
                ->name('comments.destroy');

            // ── 추천 / 비추천 (토글) ───────────────────
            Route::post('/posts/{post}/vote', [VoteController::class, 'votePost'])
                ->middleware('throttle:60,1')
                ->name('votes.post');

            Route::post('/comments/{comment}/vote', [VoteController::class, 'voteComment'])
                ->middleware('throttle:60,1')
                ->name('votes.comment');

            // ── 신고 ────────────────────────────────────
            Route::post('/posts/{post}/report', [ReportController::class, 'reportPost'])
                ->middleware('throttle:10,1')
                ->name('reports.post');

            Route::post('/comments/{comment}/report', [ReportController::class, 'reportComment'])
                ->middleware('throttle:10,1')
                ->name('reports.comment');

            // ── 실시간 투표 (The Poll) ──────────────────
            Route::get('/polls/active', [PollController::class, 'active'])->name('polls.active');

            Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])
                ->middleware('throttle:5,1')
                ->name('polls.vote');

            Route::get('/polls/{poll}/stats', [PollController::class, 'stats'])
                ->name('polls.stats');

            // ── 통계 ────────────────────────────────────
            Route::prefix('stats')->name('stats.')->group(function () {
                Route::get('/daily', [StatsController::class, 'daily'])->name('daily');
                Route::get('/monthly', [StatsController::class, 'monthly'])->name('monthly');
                Route::get('/yearly', [StatsController::class, 'yearly'])->name('yearly');
            });

            // ── 내 프로필 ───────────────────────────────
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/', [UserController::class, 'profile'])->name('show');
                Route::put('/', [UserController::class, 'updateProfile'])->name('update');
                Route::put('/password', [UserController::class, 'updatePassword'])->name('password');
                Route::delete('/', [UserController::class, 'deleteAccount'])->name('delete');

                // 내 활동 내역
                Route::get('/posts', [UserController::class, 'myPosts'])->name('posts');
                Route::get('/comments', [UserController::class, 'myComments'])->name('comments');
            });
        });

        // ─────────────────────────────────────────────
        // 관리자 API (admin 미들웨어 별도)
        // ─────────────────────────────────────────────
        Route::middleware('admin')
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {

                // 관리자 대시보드 요약
                Route::get('/summary', [AdminStatsController::class, 'summary'])
                    ->name('summary');

                // ── 사용자 관리 ─────────────────────────
                Route::prefix('users')->name('users.')->group(function () {
                    Route::get('/', [AdminUserController::class, 'index'])->name('index');
                    Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');

                    Route::post('/{user}/suspend', [AdminUserController::class, 'suspend'])
                        ->name('suspend');

                    Route::post('/{user}/ban', [AdminUserController::class, 'ban'])
                        ->name('ban');

                    Route::post('/{user}/activate', [AdminUserController::class, 'activate'])
                        ->name('activate');

                    // 회원 통계
                    Route::get('/stats/overview', [AdminUserController::class, 'statsOverview'])
                        ->name('stats');
                });

                // ── 게시판 관리 ─────────────────────────
                Route::apiResource('boards', AdminBoardController::class)
                    ->names('boards');

                Route::post('/boards/{board}/restore', [AdminBoardController::class, 'restore'])
                    ->name('boards.restore');

                // ── 게시글 관리 ─────────────────────────
                Route::prefix('posts')->name('posts.')->group(function () {
                    Route::get('/', [AdminPostController::class, 'index'])->name('index');
                    Route::post('/{post}/hide', [AdminPostController::class, 'hide'])->name('hide');
                    Route::post('/{post}/restore', [AdminPostController::class, 'restore'])->name('restore');
                    Route::delete('/{post}', [AdminPostController::class, 'destroy'])->name('destroy');
                });

                // ── 신고 처리 ───────────────────────────
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/', [AdminReportController::class, 'index'])->name('index');
                    Route::get('/{report}', [AdminReportController::class, 'show'])->name('show');
                    Route::post('/{report}/action', [AdminReportController::class, 'action'])->name('action');
                    Route::post('/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('dismiss');
                });

                // ── 투표 관리 ───────────────────────────
                Route::apiResource('polls', AdminPollController::class)
                    ->names('polls');

                Route::post('/polls/{poll}/close', [AdminPollController::class, 'close'])
                    ->name('polls.close');

                // ── 점수 가중치 관리 ────────────────────
                Route::get('/score-weights', [AdminScoreWeightController::class, 'index'])
                    ->name('score-weights.index');

                Route::put('/score-weights/{scoreWeight}', [AdminScoreWeightController::class, 'update'])
                    ->name('score-weights.update');

                // ── 집계 수동 실행 ──────────────────────
                Route::post('/aggregate/daily', [AdminStatsController::class, 'aggregateDaily'])
                    ->name('aggregate.daily');
            });
    });
});
