<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FactionScoreService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'             => $user->id,
                    'nickname'       => $user->nickname,
                    'email'          => $user->email,
                    'political_type' => $user->political_type?->value,
                    'faction_label'  => $user->political_type?->label(),
                    'faction_color'  => $user->political_type?->color(),
                    'faction_emoji'  => $user->political_type?->emoji(),
                    'is_admin'       => $user->is_admin,
                    'manner_score'   => $user->manner_score,
                    'test_completed' => $user->test_completed_at !== null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'realtimeScores' => fn () => cache()->remember('inertia:realtime_scores', 60, function () {
                return app(FactionScoreService::class)->getRealtimeScores();
            }),
        ]);
    }
}
