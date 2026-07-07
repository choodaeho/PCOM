<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserBadge;
use App\Services\UserLevelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    public function show(Request $request, User $user): Response
    {
        abort_if($user->trashed(), 404);

        $lv        = $user->level ?? 1;
        $levelInfo = UserLevelService::LEVELS[$lv] ?? UserLevelService::LEVELS[1];

        $posts = $user->posts()
            ->with('board:id,name,slug')
            ->where('status', 'published')
            ->latest()
            ->paginate(
                20,
                ['id', 'title', 'vote_up_count', 'comment_count', 'view_count', 'created_at', 'board_id', 'faction'],
                'posts_page'
            )
            ->withQueryString();

        $comments = $user->comments()
            ->with('post:id,title,board_id', 'post.board:id,slug')
            ->where('is_anonymous', false)
            ->latest()
            ->paginate(
                20,
                ['id', 'content', 'created_at', 'post_id', 'vote_up_count'],
                'comments_page'
            )
            ->withQueryString();

        $stats = [
            'post_count'    => $user->posts()->where('status', 'published')->count(),
            'comment_count' => $user->comments()->count(),
            'vote_up_count' => (int) $user->posts()->sum('vote_up_count'),
        ];

        // 취득한 뱃지 목록 — UserLevelService::BADGES 정의와 병합
        $earnedBadges = UserBadge::where('user_id', $user->id)
            ->orderBy('awarded_at')
            ->get(['badge_key', 'awarded_at'])
            ->map(function (UserBadge $badge): array {
                $def = UserLevelService::BADGES[$badge->badge_key] ?? null;
                if ($def === null) {
                    return null;
                }
                return [
                    'key'        => $badge->badge_key,
                    'emoji'      => $def['emoji'],
                    'name'       => $def['name'],
                    'desc'       => $def['desc'],
                    'category'   => $def['category'],
                    'awarded_at' => $badge->awarded_at?->format('Y-m-d'),
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('Profile/Show', [
            'profileUser' => [
                'id'             => $user->id,
                'nickname'       => $user->nickname,
                // political_type 이 null 인 경우(테스트 미완료 계정) null-safe 처리
                'political_type' => $user->political_type?->value ?? 'moderate',
                'faction_label'  => $user->political_type?->label() ?? '중도',
                'faction_color'  => $user->political_type?->color() ?? '#7F77DD',
                'level'          => $lv,
                'level_emoji'    => $levelInfo['emoji'] ?? '🌱',
                'level_name'     => $levelInfo['name']  ?? '새싹',
                'manner_score'   => $user->manner_score,
                'title'          => $user->title,
                'joined_at'      => $user->created_at?->format('Y-m-d'),
            ],
            'posts'        => $posts,
            'comments'     => $comments,
            'stats'        => $stats,
            'badges'       => $earnedBadges,
            'isOwnProfile' => $request->user()?->id === $user->id,
        ]);
    }
}
