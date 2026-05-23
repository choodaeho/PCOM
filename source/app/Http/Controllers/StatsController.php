<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FactionDailyStat;
use App\Models\FactionMonthlyStat;
use App\Models\FactionYearlyStat;
use App\Services\FactionScoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    public function __construct(private readonly FactionScoreService $scoreService) {}

    public function index(): Response
    {
        return Inertia::render('Stats/Index', [
            'realtimeScores' => $this->scoreService->getRealtimeScores(),
            'dailyData'      => FactionDailyStat::where('date', '>=', now()->subDays(30))
                ->orderBy('date')->get()->groupBy('faction_type'),
        ]);
    }

    public function daily(Request $request): Response
    {
        $days = min((int) $request->get('days', 30), 90);

        return Inertia::render('Stats/Daily', [
            'data' => FactionDailyStat::where('date', '>=', now()->subDays($days))
                ->orderBy('date')->get()->groupBy('faction_type'),
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
}
