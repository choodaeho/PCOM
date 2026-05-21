<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\FactionScoreService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(FactionScoreService $service): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => [
                    'total'     => User::count(),
                    'today_new' => User::whereDate('created_at', today())->count(),
                    'pending'   => User::where('status', 'pending')->count(),
                    'suspended' => User::where('status', 'suspended')->count(),
                    'banned'    => User::where('status', 'banned')->count(),
                ],
                'posts' => [
                    'total' => Post::count(),
                    'today' => Post::whereDate('created_at', today())->count(),
                ],
                'reports_pending' => Report::where('status', 'pending')->count(),
                'faction_scores'  => $service->getRealtimeScores(),
            ],
        ]);
    }
}
