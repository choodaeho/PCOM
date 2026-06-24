<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FactionScoreService;
use App\Services\UserLevelService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(private readonly UserLevelService $levelService)
    {
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $levelData = null;
        if ($user) {
            $levelInfo = $this->levelService->levelInfo($user->level ?? 1);
            $levelData = [
                'level'             => $user->level ?? 1,
                'experience_points' => $user->experience_points ?? 0,
                'level_emoji'       => $levelInfo['emoji'],
                'level_name'        => $levelInfo['name'],
                'current_level_xp'  => $levelInfo['current_xp'],
                'next_level_xp'     => $levelInfo['next_xp'],
            ];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? array_merge([
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
                ], $levelData) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'realtimeScores' => fn () => cache()->remember('inertia:realtime_scores', 60, function () {
                $rawScores = app(FactionScoreService::class)->getRealtimeScores();
                $meta = [
                    'conservative' => ['label' => '보수', 'emoji' => '🔴', 'color' => '#E24B4A'],
                    'moderate'     => ['label' => '중도', 'emoji' => '🟣', 'color' => '#7F77DD'],
                    'progressive'  => ['label' => '진보', 'emoji' => '🔵', 'color' => '#378ADD'],
                ];

                return collect($rawScores)
                    ->map(fn ($score, $key) => [
                        'faction_type'     => $key,
                        'label'            => $meta[$key]['label'] ?? $key,
                        'emoji'            => $meta[$key]['emoji'] ?? '',
                        'color'            => $meta[$key]['color'] ?? '#888',
                        'normalized_score' => round((float) $score, 2),
                    ])
                    ->sortByDesc('normalized_score')
                    ->values()
                    ->toArray();
            }),
        ]);
    }
}
