<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\FactionDailyStat;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\FactionScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(private readonly FactionScoreService $scoreService) {}

    /**
     * GET /api/v1/admin/summary
     *
     * 관리자 대시보드 요약 데이터.
     */
    public function summary(): JsonResponse
    {
        return response()->json([
            'users' => [
                'total'     => User::count(),
                'today_new' => User::whereDate('created_at', today())->count(),
                'pending'   => User::where('status', 'pending')->count(),
                'suspended' => User::where('status', 'suspended')->count(),
                'banned'    => User::where('status', 'banned')->count(),
            ],
            'posts' => [
                'total'     => Post::count(),
                'today_new' => Post::whereDate('created_at', today())->count(),
            ],
            'reports' => [
                'pending' => Report::where('status', 'pending')->count(),
            ],
            'faction_scores' => $this->scoreService->getRealtimeScores(),
            'today_stats'    => FactionDailyStat::todayStats(),
        ]);
    }

    /**
     * POST /api/v1/admin/aggregate/daily
     *
     * 일간 집계 수동 실행 (테스트/재집계용).
     */
    public function aggregateDaily(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date', 'before:today'],
        ]);

        $date = isset($validated['date'])
            ? \Illuminate\Support\Carbon::parse($validated['date'])
            : null;

        dispatch(fn () => $this->scoreService->aggregateDailyStats($date));

        return response()->json(['message' => '집계 작업이 시작되었습니다.']);
    }
}
