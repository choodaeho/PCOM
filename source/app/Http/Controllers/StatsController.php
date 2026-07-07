<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FactionDailyStat;
use App\Models\FactionMonthlyStat;
use App\Models\FactionYearlyStat;
use App\Models\Post;
use App\Models\Report;
use App\Services\FactionScoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public function __construct(private readonly FactionScoreService $scoreService) {}

    public function index(Request $request): Response
    {
        $period      = $request->query('period', 'daily'); // daily | weekly | monthly | yearly
        $searchDate  = (string) $request->query('date', '');
        $medalPeriod = (string) $request->query('medal_period', 'weekly'); // weekly | monthly | yearly

        $factions = ['conservative', 'moderate', 'progressive'];
        $today    = now()->toDateString();

        // 오늘 또는 어제의 최신 daily stat (keyBy faction_type)
        $latestStats = FactionDailyStat::whereDate('stat_date', $today)
            ->orWhereDate('stat_date', now()->subDay()->toDateString())
            ->orderByDesc('stat_date')
            ->get()
            ->keyBy(fn ($row) => $row->faction_type->value);

        // 실시간 점수 — FactionScoreService와 동일 로직 사용 (메인페이지와 일치)
        $realtimeScores = $this->scoreService->getRealtimeScores();
        $factionStats   = [];
        $scores         = [];

        foreach ($factions as $faction) {
            $stat  = $latestStats[$faction] ?? null;
            $score = $realtimeScores[$faction] ?? 0.0;

            $todayPostCount = Post::where('faction', $faction)
                ->whereDate('created_at', $today)
                ->where('status', 'published')
                ->count();

            $todayVoteCount = (int) Post::where('faction', $faction)
                ->whereDate('created_at', $today)
                ->sum('vote_up_count');

            $scores[$faction] = $score;

            $factionStats[$faction] = [
                'score'      => round($score, 2),
                'post_count' => ($stat?->post_count ?? 0) + $todayPostCount,
                'vote_count' => ($stat?->vote_up_count ?? 0) + $todayVoteCount,
            ];
        }

        // 랭크 계산 (점수 내림차순)
        arsort($scores);
        $ranks = array_flip(array_keys($scores));
        foreach ($factions as $faction) {
            $factionStats[$faction]['rank'] = ($ranks[$faction] ?? 0) + 1;
        }

        // period별 차트 데이터 (주간 탭 삭제됨)
        $periodData = match ($period) {
            'monthly' => $this->getMonthlyData($searchDate),
            'yearly'  => $this->getYearlyData($searchDate),
            default   => $this->getDailyData(),
        };

        // 메달 집계 (항상 현재 시점 기준)
        $medalData = $this->getMedalData($medalPeriod);

        return Inertia::render('Stats/Index', [
            'factionStats'  => $factionStats,
            'periodData'    => $periodData,
            'currentPeriod' => $period,
            'searchDate'    => $searchDate,
            'medalData'     => $medalData,
            'medalPeriod'   => $medalPeriod,
        ]);
    }

    /**
     * 일간: 항상 오늘의 실시간 점수 1개 포인트만 반환.
     */
    private function getDailyData(): array
    {
        $scores = $this->scoreService->getRealtimeScores();

        return [[
            'date'         => now()->toDateString(),
            'conservative' => round((float) ($scores['conservative'] ?? 0), 2),
            'moderate'     => round((float) ($scores['moderate']     ?? 0), 2),
            'progressive'  => round((float) ($scores['progressive']  ?? 0), 2),
        ]];
    }

    /**
     * 월간: 해당 연도의 1월 ~ 마지막 완료 월 데이터.
     * - 현재 연도: 1월 ~ (현재 월 - 1)  (미도래 월 표기 안 함)
     * - 과거 연도: 1월 ~ 12월
     * - searchDate: "YYYY" 형식 (연도 선택용)
     */
    private function getMonthlyData(string $searchDate = ''): array
    {
        $year = ($searchDate !== '' && ctype_digit(substr($searchDate, 0, 4)))
            ? (int) substr($searchDate, 0, 4)
            : now()->year;

        $lastMonth = ($year === now()->year)
            ? now()->month - 1   // 현재 연도는 전월까지
            : 12;                // 과거 연도는 12월까지

        if ($lastMonth < 1) {
            return [];  // 1월인 경우 아직 완료된 월 없음
        }

        $from = sprintf('%04d-01', $year);
        $to   = sprintf('%04d-%02d', $year, $lastMonth);

        $rows = FactionMonthlyStat::whereBetween('stat_year_month', [$from, $to])
            ->orderBy('stat_year_month')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $ym = $row->stat_year_month;
            if (!isset($map[$ym])) {
                $map[$ym] = ['date' => $ym, 'conservative' => 0.0, 'moderate' => 0.0, 'progressive' => 0.0];
            }
            $faction      = $row->faction_type instanceof \App\Enums\FactionType
                ? $row->faction_type->value
                : (string) $row->faction_type;
            $map[$ym][$faction] = round((float) ($row->total_raw_score ?? 0), 2);
        }

        return array_values($map);
    }

    /**
     * 연간: 해당 연도의 월별 수치 + 연간 합계 포인트.
     * - 월별: getMonthlyData()와 동일 (1월 ~ 마지막 완료 월)
     * - 연간합계: FactionYearlyStat이 있으면 사용, 없으면 월별 합산
     */
    private function getYearlyData(string $searchDate = ''): array
    {
        $year = ($searchDate !== '' && ctype_digit(substr($searchDate, 0, 4)))
            ? (int) substr($searchDate, 0, 4)
            : now()->year;

        $monthlyData = $this->getMonthlyData((string) $year);

        if (empty($monthlyData)) {
            return [];
        }

        // 연간합계: DB에 연간 집계가 있으면 사용, 없으면 월별 합산
        $totals = ['conservative' => 0.0, 'moderate' => 0.0, 'progressive' => 0.0];
        $yearlyRows = FactionYearlyStat::where('stat_year', $year)->get();

        if ($yearlyRows->isNotEmpty()) {
            foreach ($yearlyRows as $row) {
                $faction      = $row->faction_type instanceof \App\Enums\FactionType
                    ? $row->faction_type->value
                    : (string) $row->faction_type;
                $totals[$faction] = round((float) ($row->total_raw_score ?? 0), 2);
            }
        } else {
            foreach ($monthlyData as $m) {
                $totals['conservative'] += $m['conservative'];
                $totals['moderate']     += $m['moderate'];
                $totals['progressive']  += $m['progressive'];
            }
            $totals = array_map(fn ($v) => round($v, 2), $totals);
        }

        $monthlyData[] = array_merge(['date' => '연간합계'], $totals);

        return $monthlyData;
    }

    /**
     * 메달 집계 — 각 날짜별 진영 순위를 산출해 금/은/동 개수를 카운팅.
     * 항상 현재 시점(today) 기준으로 period 범위를 결정.
     */
    private function getMedalData(string $period = 'weekly'): array
    {
        $base = now();

        [$from, $to] = match ($period) {
            'monthly' => [
                $base->copy()->startOfMonth()->toDateString(),
                $base->copy()->endOfMonth()->toDateString(),
            ],
            'yearly'  => [
                $base->copy()->startOfYear()->toDateString(),
                $base->copy()->endOfYear()->toDateString(),
            ],
            default   => [   // weekly
                $base->copy()->startOfWeek()->toDateString(),
                $base->copy()->endOfWeek()->toDateString(),
            ],
        };

        // 날짜별 진영 순위 — WHERE 는 윈도우 함수 이전에 적용되므로 서브쿼리 불필요
        $rankedRows = DB::table('factions_daily_stats')
            ->selectRaw(
                'stat_date::text AS stat_date,
                 faction_type,
                 raw_score,
                 RANK() OVER (PARTITION BY stat_date ORDER BY raw_score DESC) AS day_rank'
            )
            ->whereBetween('stat_date', [$from, $to])
            ->orderBy('stat_date')
            ->get();

        $medals = [
            'conservative' => ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0],
            'moderate'     => ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0],
            'progressive'  => ['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0],
        ];
        $medalNames = [1 => 'gold', 2 => 'silver', 3 => 'bronze'];

        foreach ($rankedRows as $row) {
            $rank    = (int) $row->day_rank;
            $faction = $row->faction_type;
            $medal   = $medalNames[$rank] ?? null;
            if ($medal !== null && isset($medals[$faction])) {
                $medals[$faction][$medal]++;
                $medals[$faction]['total']++;
            }
        }

        // 금→은→동 순 정렬
        uasort($medals, static function (array $a, array $b): int {
            if ($a['gold']   !== $b['gold'])   return $b['gold']   - $a['gold'];
            if ($a['silver'] !== $b['silver']) return $b['silver'] - $a['silver'];
            return $b['bronze'] - $a['bronze'];
        });

        $totalDays = $rankedRows->pluck('stat_date')->unique()->count();

        return [
            'medals'     => $medals,
            'period'     => $period,
            'from'       => $from,
            'to'         => $to,
            'total_days' => $totalDays,
        ];
    }

    public function personal(Request $request): Response
    {
        $user = $request->user();

        // 내 게시글 통계
        $postStats = Post::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total, SUM(view_count) as total_views, SUM(vote_up_count) as total_votes_up, SUM(vote_down_count) as total_votes_down, SUM(comment_count) as total_comments')
            ->first();

        // 게시판별 게시글 수
        $postsByBoard = Post::where('posts.user_id', $user->id)
            ->where('posts.status', 'published')
            ->join('boards', 'posts.board_id', '=', 'boards.id')
            ->selectRaw('boards.name as board_name, boards.board_type, COUNT(*) as count')
            ->groupBy('boards.id', 'boards.name', 'boards.board_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 최근 30일 활동 (날짜별 게시글 수)
        $activityByDay = Post::where('user_id', $user->id)
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 신고 수
        $reportCount = Report::where('reporter_id', $user->id)->count();
        $reportedCount = Report::whereHasMorph(
            'reportable',
            [Post::class],
            fn ($q) => $q->where('user_id', $user->id)
        )->count();

        $politicalType = $user->political_type?->value ?? $user->political_type ?? 'moderate';
        $factionLabel  = match ($politicalType) {
            'conservative' => '보수',
            'progressive'  => '진보',
            default        => '중도',
        };
        $factionColor  = match ($politicalType) {
            'conservative' => '#E24B4A',
            'progressive'  => '#378ADD',
            default        => '#7F77DD',
        };

        return Inertia::render('Stats/Personal', [
            'postStats'     => $postStats,
            'postsByBoard'  => $postsByBoard,
            'activityByDay' => $activityByDay,
            'reportCount'   => $reportCount,
            'reportedCount' => $reportedCount,
            'user'          => [
                'nickname'       => $user->nickname,
                'political_type' => $politicalType,
                'faction_label'  => $factionLabel,
                'faction_color'  => $factionColor,
                'manner_score'   => $user->manner_score,
                'test_score'     => $user->test_score,
                'joined_at'      => $user->created_at->format('Y. m. d'),
            ],
        ]);
    }

    public function daily(Request $request): Response
    {
        $days = min((int) $request->get('days', 30), 90);

        return Inertia::render('Stats/Daily', [
            'data' => FactionDailyStat::where('stat_date', '>=', now()->subDays($days)->toDateString())
                ->orderBy('stat_date')->get()->groupBy('faction_type'),
            'days' => $days,
        ]);
    }

    public function monthly(Request $request): Response
    {
        $months = min((int) $request->get('months', 12), 36);

        return Inertia::render('Stats/Monthly', [
            'data'   => FactionMonthlyStat::where('stat_year_month', '>=', now()->subMonths($months)->format('Y-m'))
                ->orderBy('stat_year_month')->get()->groupBy('faction_type'),
            'months' => $months,
        ]);
    }

    public function yearly(Request $request): Response
    {
        $years = min((int) $request->get('years', 5), 10);

        return Inertia::render('Stats/Yearly', [
            'data'  => FactionYearlyStat::where('stat_year', '>=', now()->year - $years)
                ->orderBy('stat_year')->get()->groupBy('faction_type'),
            'years' => $years,
        ]);
    }

    public function ranking(Request $request): Response
    {
        $category = $request->query('category', 'posts');   // posts | votes | manner
        $faction  = $request->query('faction', 'all');       // all | conservative | moderate | progressive

        // 기본 조건: 활성 계정, 성향 테스트 완료, 관리자 제외
        $base = \App\Models\User::where('status', 'active')
            ->where('is_admin', false)
            ->whereNotNull('test_completed_at');

        if ($faction !== 'all') {
            $base->where('political_type', $faction);
        }

        $users = match($category) {
            // 인기왕: 추천을 1회 이상 받은 사용자만
            'votes'  => (clone $base)
                            ->whereHas('posts', fn ($q) => $q->where('status', 'published')->where('vote_up_count', '>', 0))
                            ->orderByDesc(
                                \App\Models\Post::selectRaw('SUM(vote_up_count)')
                                    ->whereColumn('user_id', 'users.id')
                                    ->where('status', 'published')
                            )->limit(50)->get(),
            // 매너왕: 게시글 1개 이상 작성한 사용자, 매너 점수 내림차순
            'manner' => (clone $base)
                            ->whereHas('posts', fn ($q) => $q->where('status', 'published'))
                            ->orderByDesc('manner_score')
                            ->limit(50)->get(),
            // 레벨왕: 레벨 내림차순, 동일 레벨은 XP 내림차순
            'level'  => (clone $base)
                            ->where('level', '>', 1)
                            ->orderByDesc('level')
                            ->orderByDesc('experience_points')
                            ->limit(50)->get(),
            // 게시글왕: 게시글 1개 이상 작성한 사용자만
            default  => (clone $base)
                            ->whereHas('posts', fn ($q) => $q->where('status', 'published'))
                            ->orderByDesc(
                                \App\Models\Post::selectRaw('COUNT(*)')
                                    ->whereColumn('user_id', 'users.id')
                                    ->where('status', 'published')
                            )->limit(50)->get(),
        };

        $ranked = $users->map(function ($u, $i) use ($category) {
            $postCount  = \App\Models\Post::where('user_id', $u->id)->where('status', 'published')->count();
            $totalVotes = \App\Models\Post::where('user_id', $u->id)->where('status', 'published')->sum('vote_up_count');

            $factionType = $u->political_type instanceof \App\Enums\FactionType
                ? $u->political_type->value
                : $u->political_type;

            return [
                'rank'           => $i + 1,
                'id'             => $u->id,
                'nickname'       => $u->nickname,
                'political_type' => $factionType,
                'faction_label'  => match($factionType) {
                    'conservative' => '보수',
                    'progressive'  => '진보',
                    default        => '중도',
                },
                'faction_color'  => match($factionType) {
                    'conservative' => '#E24B4A',
                    'progressive'  => '#378ADD',
                    default        => '#7F77DD',
                },
                'post_count'    => $postCount,
                'total_votes'   => (int) $totalVotes,
                'manner_score'  => $u->manner_score,
                'level'         => $u->level ?? 1,
                'experience_points' => $u->experience_points ?? 0,
                'level_emoji'   => \App\Services\UserLevelService::LEVELS[$u->level ?? 1]['emoji'] ?? '🌱',
                'level_name'    => \App\Services\UserLevelService::LEVELS[$u->level ?? 1]['name'] ?? '새싹',
                'title'         => $u->title,
                'joined_at'     => $u->created_at->format('Y. m. d'),
            ];
        });

        return Inertia::render('Stats/Ranking', [
            'users'    => $ranked,
            'category' => $category,
            'faction'  => $faction,
        ]);
    }

}