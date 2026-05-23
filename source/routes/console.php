<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/**
 * 폴릿(Polit) 스케줄 등록
 *
 * 실행 확인: php artisan schedule:list
 * 로컬 실행: php artisan schedule:work
 */

// ─────────────────────────────────────────────
// 진영 점수 집계
// ─────────────────────────────────────────────

// 일간 집계: 매일 00:05 (전일 데이터)
Schedule::command('polit:aggregate-daily')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/aggregate-daily.log'));

// 월간 롤업: 매월 1일 00:10
Schedule::command('polit:aggregate-daily --monthly')
    ->monthlyOn(1, '00:10')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/aggregate-monthly.log'));

// 연간 롤업: 매년 1월 1일 00:15
Schedule::command('polit:aggregate-daily --yearly')
    ->yearlyOn(1, 1, '00:15')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/aggregate-yearly.log'));

// ─────────────────────────────────────────────
// 실시간 점수 캐시 갱신
// ─────────────────────────────────────────────

// Redis 실시간 점수 캐시를 1분마다 갱신
Schedule::call(function () {
    app(\App\Services\FactionScoreService::class)->refreshRealtimeCache();
})->everyMinute()->name('realtime-score-refresh')->withoutOverlapping();

// ─────────────────────────────────────────────
// 일시 정지 만료 사용자 자동 복구
// ─────────────────────────────────────────────

Schedule::call(function () {
    \App\Models\User::where('status', 'suspended')
        ->where('suspended_until', '<=', now())
        ->update(['status' => 'active', 'suspended_until' => null]);
})->hourly()->name('auto-restore-suspended-users');
