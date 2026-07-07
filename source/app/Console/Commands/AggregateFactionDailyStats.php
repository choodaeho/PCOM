<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FactionDailyStat;
use App\Models\FactionMonthlyStat;
use App\Models\FactionYearlyStat;
use App\Services\FactionScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * 진영 점수 집계 커맨드.
 *
 * 스케줄러 등록 (routes/console.php):
 *   Schedule::command('polit:aggregate-daily')->dailyAt('00:05');
 *   Schedule::command('polit:aggregate-daily --monthly')->monthlyOn(1, '00:10');
 *   Schedule::command('polit:aggregate-daily --yearly')->yearlyOn(1, 1, '00:15');
 */
class AggregateFactionDailyStats extends Command
{
    protected $signature = 'polit:aggregate-daily
                            {--date=      : 집계 기준일 (YYYY-MM-DD, 기본: 어제)}
                            {--monthly    : 월간 롤업도 함께 실행}
                            {--yearly     : 연간 롤업도 함께 실행}';

    protected $description = '진영별 일간/월간/연간 점수를 집계하여 stats 테이블에 저장합니다.';

    public function __construct(private readonly FactionScoreService $scoreService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dateStr = $this->option('date');
        $date    = $dateStr ? Carbon::parse($dateStr) : now()->subDay();

        $this->info("📊 [일간] {$date->toDateString()} 진영 점수 집계 시작...");

        try {
            $this->scoreService->aggregateDailyStats($date);
            $this->info("✅ 일간 집계 완료: {$date->toDateString()}");

            // 결과 출력
            $stats = FactionDailyStat::where('stat_date', $date->toDateString())
                ->orderBy('rank')
                ->get();

            $this->table(
                ['순위', '진영', '게시물', '댓글', '추천', '활성유저', '정규화점수'],
                $stats->map(fn ($s) => [
                    "#{$s->rank}",
                    $s->faction_type->label(),
                    $s->post_count,
                    $s->comment_count,
                    $s->vote_up_count,
                    $s->active_user_count,
                    number_format($s->normalized_score, 4),
                ])
            );

        } catch (\Throwable $e) {
            $this->error("❌ 일간 집계 실패: {$e->getMessage()}");
            return self::FAILURE;
        }

        // 월간 롤업
        if ($this->option('monthly')) {
            $this->aggregateMonthly($date);
        }

        // 연간 롤업
        if ($this->option('yearly')) {
            $this->aggregateYearly($date);
        }

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // 내부 헬퍼
    // -------------------------------------------------------------------------

    private function aggregateMonthly(Carbon $date): void
    {
        // $date는 이미 "어제" → 스케줄이 익월 1일 00:10에 실행되면 $date = 전월 말일
        // subMonth() 제거: June 30을 받으면 "2026-06"이 올바른 집계 대상
        $yearMonth = $date->format('Y-m');
        $this->info("📅 [월간] {$yearMonth} 롤업 시작...");

        try {
            $factions = ['conservative', 'moderate', 'progressive'];

            foreach ($factions as $factionVal) {
                $dailyStats = FactionDailyStat::where('faction_type', $factionVal)
                    ->where('stat_date', 'like', "{$yearMonth}%")
                    ->get();

                if ($dailyStats->isEmpty()) {
                    continue;
                }

                FactionMonthlyStat::updateOrCreate(
                    ['faction_type' => $factionVal, 'stat_year_month' => $yearMonth],
                    [
                        'post_count'             => $dailyStats->sum('post_count'),
                        'comment_count'          => $dailyStats->sum('comment_count'),
                        'vote_up_count'          => $dailyStats->sum('vote_up_count'),
                        'vote_down_count'        => $dailyStats->sum('vote_down_count'),
                        'report_count'           => $dailyStats->sum('report_count'),
                        'avg_active_user_count'  => round($dailyStats->avg('active_user_count'), 2),
                        'peak_active_user_count' => $dailyStats->max('active_user_count'),
                        'total_raw_score'        => round($dailyStats->sum('raw_score'), 4),
                        'avg_normalized_score'   => round($dailyStats->avg('normalized_score'), 6),
                        'calculated_at'          => now(),
                    ]
                );
            }

            $this->info("✅ 월간 롤업 완료: {$yearMonth}");

        } catch (\Throwable $e) {
            $this->error("❌ 월간 롤업 실패: {$e->getMessage()}");
        }
    }

    private function aggregateYearly(Carbon $date): void
    {
        // $date는 "어제" → 1월 1일 00:15에 실행되면 $date = 12월 31일(전년도)
        // subYear() 제거: Dec 31 2025를 받으면 year = 2025가 올바른 집계 대상
        $year = $date->year;
        $this->info("📆 [연간] {$year}년 롤업 시작...");

        try {
            $factions = ['conservative', 'moderate', 'progressive'];

            foreach ($factions as $factionVal) {
                $monthlyStats = FactionMonthlyStat::where('faction_type', $factionVal)
                    ->where('stat_year_month', 'like', "{$year}-%")
                    ->get();

                if ($monthlyStats->isEmpty()) {
                    continue;
                }

                FactionYearlyStat::updateOrCreate(
                    ['faction_type' => $factionVal, 'stat_year' => $year],
                    [
                        'post_count'             => $monthlyStats->sum('post_count'),
                        'comment_count'          => $monthlyStats->sum('comment_count'),
                        'vote_up_count'          => $monthlyStats->sum('vote_up_count'),
                        'vote_down_count'        => $monthlyStats->sum('vote_down_count'),
                        'report_count'           => $monthlyStats->sum('report_count'),
                        'avg_active_user_count'  => round($monthlyStats->avg('avg_active_user_count'), 2),
                        'peak_active_user_count' => $monthlyStats->max('peak_active_user_count'),
                        'total_raw_score'        => round($monthlyStats->sum('total_raw_score'), 4),
                        'avg_normalized_score'   => round($monthlyStats->avg('avg_normalized_score'), 6),
                        'calculated_at'          => now(),
                    ]
                );
            }

            $this->info("✅ 연간 롤업 완료: {$year}년");

        } catch (\Throwable $e) {
            $this->error("❌ 연간 롤업 실패: {$e->getMessage()}");
        }
    }
}
