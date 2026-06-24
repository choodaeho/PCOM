<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FactionDailyStat;
use App\Models\FactionMonthlyStat;
use App\Models\FactionYearlyStat;
use App\Models\Post;
use App\Models\Report;
use App\Services\FactionScoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public function __construct(private readonly FactionScoreService $scoreService) {}

    public function index(Request $request): Response
    {
        $period  = $request->query('period', 'daily'); // daily | monthly | yearly
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

        // period별 차트 데이터
        $periodData = match ($period) {
            'monthly' => $this->getMonthlyData(),
            'yearly'  => $this->getYearlyData(),
            default   => $this->getDailyData(30),
        };

        return Inertia::render('Stats/Index', [
            'factionStats'  => $factionStats,
            'periodData'    => $periodData,
            'currentPeriod' => $period,
        ]);
    }

    private function getDailyData(int $days = 30): array
    {
        $rows = FactionDailyStat::where('stat_date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('stat_date')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $date = $row->stat_date->toDateString();
            if (!isset($map[$date])) {
                $map[$date] = ['date' => $date, 'conservative' => 0, 'moderate' => 0, 'progressive' => 0];
            }
            $faction         = $row->faction_type instanceof \App\Enums\FactionType
                ? $row->faction_type->value
                : $row->faction_type;
            $map[$date][$faction] = $row->raw_score ?? 0;
        }

        return array_values($map);
    }

    private function getMonthlyData(): array
    {
        $rows = FactionMonthlyStat::orderBy('stat_year_month')
            ->limit(24)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $row->stat_year_month;
            if (!isset($map[$key])) {
                $map[$key] = ['date' => $key, 'conservative' => 0, 'moderate' => 0, 'progressive' => 0];
            }
            $faction      = $row->faction_type instanceof \App\Enums\FactionType
                ? $row->faction_type->value
                : $row->faction_type;
            $map[$key][$faction] = $row->total_raw_score ?? $row->raw_score ?? 0;
        }

        return array_values($map);
    }

    private function getYearlyData(): array
    {
        $rows = FactionYearlyStat::orderBy('stat_year')
            ->limit(10)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = (string) $row->stat_year;
            if (!isset($map[$key])) {
                $map[$key] = ['date' => $key, 'conservative' => 0, 'moderate' => 0, 'progressive' => 0];
            }
            $faction      = $row->faction_type instanceof \App\Enums\FactionType
                ? $row->faction_type->value
                : $row->faction_type;
            $map[$key][$faction] = $row->total_raw_score ?? $row->raw_score ?? 0;
        }

        return array_values($map);
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