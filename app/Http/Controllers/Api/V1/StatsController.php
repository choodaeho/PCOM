<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FactionDailyStat;
use App\Models\FactionMonthlyStat;
use App\Models\FactionYearlyStat;
use App\Services\FactionScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(private readonly FactionScoreService $scoreService) {}

    /**
     * GET /api/v1/stats/realtime
     *
     * 실시간 진영 점수 (헤더 위젯용).
     * 비로그인도 접근 가능, 1분 캐시.
     */
    public function realtime(): JsonResponse
    {
        $scores = $this->scoreService->getRealtimeScores();

        $factions = [
            'conservative' => ['label' => '보수', 'emoji' => '🔵', 'color' => '#378ADD'],
            'moderate'     => ['label' => '중도', 'emoji' => '🟣', 'color' => '#7F77DD'],
            'progressive'  => ['label' => '진보', 'emoji' => '🔴', 'color' => '#E24B4A'],
        ];

        $result = [];
        foreach ($factions as $key => $meta) {
            $result[] = [
                'faction'          => $key,
                'label'            => $meta['label'],
                'emoji'            => $meta['emoji'],
                'color'            => $meta['color'],
                'normalized_score' => $scores[$key] ?? 0.0,
            ];
        }

        // 점수 내림차순 정렬 → 1위 진영 강조용
        usort($result, fn ($a, $b) => $b['normalized_score'] <=> $a['normalized_score']);
        foreach ($result as $rank => &$item) {
            $item['rank'] = $rank + 1;
        }
        unset($item);

        return response()->json([
            'scores'     => $result,
            'updated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * GET /api/v1/stats/daily
     *
     * 일간 진영 점수 이력.
     * Query: ?days=30 (max 90)
     */
    public function daily(Request $request): JsonResponse
    {
        $days = min((int) $request->query('days', 30), 90);

        $stats = FactionDailyStat::where('stat_date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('stat_date')
            ->get()
            ->groupBy(fn ($s) => $s->stat_date->toDateString());

        return response()->json(['data' => $stats, 'period_days' => $days]);
    }

    /**
     * GET /api/v1/stats/monthly
     *
     * 월간 진영 점수 이력.
     * Query: ?months=12 (max 36)
     */
    public function monthly(Request $request): JsonResponse
    {
        $months = min((int) $request->query('months', 12), 36);

        $from  = now()->subMonths($months)->format('Y-m');
        $stats = FactionMonthlyStat::where('stat_year_month', '>=', $from)
            ->orderBy('stat_year_month')
            ->get()
            ->groupBy('stat_year_month');

        return response()->json(['data' => $stats, 'period_months' => $months]);
    }

    /**
     * GET /api/v1/stats/yearly
     *
     * 연간 진영 점수 이력.
     * Query: ?years=5 (max 10)
     */
    public function yearly(Request $request): JsonResponse
    {
        $years = min((int) $request->query('years', 5), 10);

        $stats = FactionYearlyStat::where('stat_year', '>=', now()->year - $years)
            ->orderBy('stat_year')
            ->get()
            ->groupBy('stat_year');

        return response()->json(['data' => $stats, 'period_years' => $years]);
    }
}
