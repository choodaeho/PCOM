<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\PostImageController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PoliticalTestController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController as AdminUser;
use App\Http\Controllers\Admin\BoardController as AdminBoard;
use App\Http\Controllers\Admin\PostController as AdminPost;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\PollController as AdminPoll;
use App\Http\Controllers\Admin\ScoreWeightController as AdminScoreWeight;
use App\Http\Controllers\Admin\LegalDocumentController as AdminLegal;
use App\Http\Controllers\Admin\DeletionRequestAdminController as AdminDeletionRequest;
use App\Http\Controllers\DeletionRequestController;
use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════════════════════════════
// 랜딩 페이지
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/', fn () => inertia('Home'))->name('home');

// ═══════════════════════════════════════════════════════════════════════════════
// 법적 문서 (이용약관 / 개인정보처리방침) — 비로그인 허용
// ═══════════════════════════════════════════════════════════════════════════════

Route::prefix('/legal')->name('legal.')->group(function () {
    Route::get('/terms',            [LegalController::class,           'terms'])          ->name('terms');
    Route::get('/privacy',          [LegalController::class,           'privacy'])         ->name('privacy');
    Route::get('/youth-protection', [LegalController::class,           'youthProtection'])->name('youth-protection');
    Route::get('/deletion-request', [DeletionRequestController::class, 'create'])         ->name('deletion-request');
    Route::post('/deletion-request',[DeletionRequestController::class, 'store'])          ->name('deletion-request.store');
});

// ═══════════════════════════════════════════════════════════════════════════════
// 인증 (비로그인 전용)
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware('guest')->group(function () {
    // 이메일 로그인
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

    // 이메일 회원가입
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

    // 소셜 로그인 (카카오·네이버·구글)
    Route::get('/auth/{provider}', [SocialLoginController::class, 'redirect'])
        ->whereIn('provider', ['kakao', 'naver', 'google'])
        ->name('social.redirect');

    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->whereIn('provider', ['kakao', 'naver', 'google'])
        ->name('social.callback');
});

// ─────────────────────────────────────────────
// 이메일 인증 (로그인 후, 인증 전)
// ─────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // 로그아웃
    Route::post('/logout', LogoutController::class)->name('logout');
});

// ═══════════════════════════════════════════════════════════════════════════════
// 성향 테스트 (비로그인 포함 누구나 접근 가능)
// 로그인 여부에 따른 저장/비저장 분기는 컨트롤러에서 처리.
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/political-test', [PoliticalTestController::class, 'show'])
    ->name('political-test.show');

Route::post('/political-test', [PoliticalTestController::class, 'submit'])
    ->name('political-test.submit');

Route::get('/political-test/result', [PoliticalTestController::class, 'result'])
    ->name('political-test.result');

// ═══════════════════════════════════════════════════════════════════════════════
// 툴박스 (비로그인 허용) — 로또번호생성기, 운세 등 유입 기능
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/tools', [ToolsController::class, 'index'])->name('tools.index');

// ─────────────────────────────────────────────
// Quill 에디터 이미지 업로드 (로그인 + 인증 완료 필요)
// ─────────────────────────────────────────────
Route::middleware(['auth', 'verified'])
    ->post('/posts/upload-image', [PostImageController::class, 'upload'])
    ->name('posts.upload-image');

// ═══════════════════════════════════════════════════════════════════════════════
// 커뮤니티 공개 열람 (비로그인 허용)
// — 게시판 목록 / 게시판 글 목록 / 글 상세
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/boards', [BoardController::class, 'index'])->name('boards.index');

Route::prefix('/boards/{board:slug}')->group(function () {
    Route::get('/', [BoardController::class, 'show'])->name('boards.show');
    // {post}는 숫자 ID만 허용 — "create" 같은 문자열은 이 라우트에 매칭되지 않음
    Route::get('/posts/{post}', [PostController::class, 'show'])
        ->name('posts.show')
        ->whereNumber('post');
});

// ═══════════════════════════════════════════════════════════════════════════════
// 커뮤니티 쓰기/상호작용
// 조건: 로그인 + 이메일 인증 + 계정 활성 + 성향 테스트 완료
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'verified', 'user.active', 'political.test'])->group(function () {

    // ─────────────────────────────────────────────
    // 게시글 CRUD (faction.access 미들웨어)
    //   아지트 → 본인 진영 유저만 작성/수정/삭제 가능
    //   전쟁터 → 전 진영 유저 작성 가능
    // ─────────────────────────────────────────────
    Route::middleware('faction.access')->prefix('/boards/{board:slug}')->group(function () {
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
        Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });

    // ─────────────────────────────────────────────
    // 댓글 (board 없이 post_id로 식별)
    // ─────────────────────────────────────────────
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->name('comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])
        ->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy');

    // ─────────────────────────────────────────────
    // 추천/비추천 (POST → 토글 방식)
    // ─────────────────────────────────────────────
    Route::post('/posts/{post}/vote', [VoteController::class, 'votePost'])
        ->name('votes.post');
    Route::post('/comments/{comment}/vote', [VoteController::class, 'voteComment'])
        ->name('votes.comment');

    // ─────────────────────────────────────────────
    // 신고
    // ─────────────────────────────────────────────
    Route::post('/posts/{post}/report', [ReportController::class, 'reportPost'])
        ->name('reports.post');
    Route::post('/comments/{comment}/report', [ReportController::class, 'reportComment'])
        ->name('reports.comment');

    // ─────────────────────────────────────────────
    // 실시간 투표 (The Poll)
    // ─────────────────────────────────────────────
    Route::get('/polls/active', [PollController::class, 'active'])->name('polls.active');
    Route::post('/polls/{poll}/vote', [PollController::class, 'vote'])->name('polls.vote');

    // ─────────────────────────────────────────────
    // 통계 대시보드
    // ─────────────────────────────────────────────
    Route::prefix('/stats')->name('stats.')->group(function () {
        Route::get('/', [StatsController::class, 'index'])->name('index');
        Route::get('/daily', [StatsController::class, 'daily'])->name('daily');
        Route::get('/monthly', [StatsController::class, 'monthly'])->name('monthly');
        Route::get('/yearly', [StatsController::class, 'yearly'])->name('yearly');
    });

    // ─────────────────────────────────────────────
    // 프로필
    // ─────────────────────────────────────────────
    Route::prefix('/profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// 랭킹 (비로그인 허용)
// ═══════════════════════════════════════════════════════════════════════════════

Route::prefix('/stats')->name('stats.')->group(function () {
    Route::get('/ranking', [StatsController::class, 'ranking'])->name('ranking');
});

// ═══════════════════════════════════════════════════════════════════════════════
// 관리자 인증
// /admin/login  — 1단계: 이메일 + 비밀번호
// /admin/login/2fa — 2단계: Google Authenticator OTP
// ═══════════════════════════════════════════════════════════════════════════════

Route::prefix('/admin')->name('admin.')->group(function () {
    // 관리자 로그인 폼 / 제출 (로그인 여부 무관)
    Route::get('/login', [AdminLoginController::class, 'showForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');

    // 2FA 폼 / 검증 (1단계 통과 후 세션에 pending_id가 있어야 접근 가능)
    Route::get('/login/2fa', [AdminLoginController::class, 'show2fa'])->name('login.2fa');
    Route::post('/login/2fa', [AdminLoginController::class, 'verify2fa'])->name('login.2fa.verify');

    // 로그아웃 (인증 여부 무관 — 로그인 도중 중단도 허용)
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// ═══════════════════════════════════════════════════════════════════════════════
// 관리자 패널
// 조건: 로그인 + is_admin = true + Google OTP 2단계 인증 완료 (admin.auth 미들웨어)
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware('admin.auth')
    ->prefix('/admin')
    ->name('admin.')
    ->group(function () {
        // 대시보드
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        // 사용자 관리
        Route::prefix('/users')->name('users.')->group(function () {
            Route::get('/', [AdminUser::class, 'index'])->name('index');
            Route::get('/{user}', [AdminUser::class, 'show'])->name('show');
            Route::post('/{user}/suspend', [AdminUser::class, 'suspend'])->name('suspend');
            Route::post('/{user}/ban', [AdminUser::class, 'ban'])->name('ban');
            Route::post('/{user}/activate', [AdminUser::class, 'activate'])->name('activate');
        });

        // 게시판 관리
        Route::prefix('/boards')->name('boards.')->group(function () {
            Route::get('/', [AdminBoard::class, 'index'])->name('index');
            Route::get('/create', [AdminBoard::class, 'create'])->name('create');
            Route::post('/', [AdminBoard::class, 'store'])->name('store');
            Route::get('/{board}/edit', [AdminBoard::class, 'edit'])->name('edit');
            Route::put('/{board}', [AdminBoard::class, 'update'])->name('update');
            Route::delete('/{board}', [AdminBoard::class, 'destroy'])->name('destroy');
            Route::post('/{board}/restore', [AdminBoard::class, 'restore'])->name('restore');
        });

        // 게시글 관리
        Route::prefix('/posts')->name('posts.')->group(function () {
            Route::get('/', [AdminPost::class, 'index'])->name('index');
            Route::post('/{post}/hide', [AdminPost::class, 'hide'])->name('hide');
            Route::post('/{post}/restore', [AdminPost::class, 'restore'])->name('restore');
            Route::delete('/{post}', [AdminPost::class, 'destroy'])->name('destroy');
        });

        // 신고 처리
        Route::prefix('/reports')->name('reports.')->group(function () {
            Route::get('/', [AdminReport::class, 'index'])->name('index');
            Route::get('/{report}', [AdminReport::class, 'show'])->name('show');
            Route::post('/{report}/action', [AdminReport::class, 'action'])->name('action');
            Route::post('/{report}/dismiss', [AdminReport::class, 'dismiss'])->name('dismiss');
            Route::post('/{report}/restore-content', [AdminReport::class, 'restoreContent'])->name('restore-content');
        });

        // 실시간 투표 관리
        Route::prefix('/polls')->name('polls.')->group(function () {
            Route::get('/', [AdminPoll::class, 'index'])->name('index');
            Route::get('/create', [AdminPoll::class, 'create'])->name('create');
            Route::post('/', [AdminPoll::class, 'store'])->name('store');
            Route::get('/{poll}/edit', [AdminPoll::class, 'edit'])->name('edit');
            Route::put('/{poll}', [AdminPoll::class, 'update'])->name('update');
            Route::post('/{poll}/close', [AdminPoll::class, 'close'])->name('close');
            Route::delete('/{poll}', [AdminPoll::class, 'destroy'])->name('destroy');
        });

        // 점수 가중치 관리
        Route::prefix('/score-weights')->name('score-weights.')->group(function () {
            Route::get('/', [AdminScoreWeight::class, 'index'])->name('index');
            Route::put('/{scoreWeight}', [AdminScoreWeight::class, 'update'])->name('update');
        });

        // 약관/법적 문서 관리
        Route::prefix('/legal')->name('legal.')->group(function () {
            Route::get('/', [AdminLegal::class, 'index'])->name('index');
            Route::get('/create', [AdminLegal::class, 'create'])->name('create');
            Route::post('/', [AdminLegal::class, 'store'])->name('store');
            Route::get('/{legal}/edit', [AdminLegal::class, 'edit'])->name('edit');
            Route::put('/{legal}', [AdminLegal::class, 'update'])->name('update');
            Route::post('/{legal}/set-current', [AdminLegal::class, 'setCurrent'])->name('set-current');
            Route::delete('/{legal}', [AdminLegal::class, 'destroy'])->name('destroy');
        });

        // 삭제 요청 관리
        Route::prefix('/deletion-requests')->name('deletion-requests.')->group(function () {
            Route::get('/', [AdminDeletionRequest::class, 'index'])->name('index');
            Route::get('/{deletionRequest}', [AdminDeletionRequest::class, 'show'])->name('show');
            Route::post('/{deletionRequest}/confirm', [AdminDeletionRequest::class, 'confirm'])->name('confirm');
            Route::post('/{deletionRequest}/restore', [AdminDeletionRequest::class, 'restore'])->name('restore');
        });
    });
