<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BoardType;
use App\Enums\FactionType;
use App\Models\Board;
use App\Models\Post;
use App\Services\UserLevelService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly UserLevelService $levelService) {}

    public function index(): Response
    {
        // 🔥 인기글: 전쟁터·놀이터, 추천 많은 순 (최근 7일 내)
        // 비로그인도 열람 가능 — 아지트(faction-restricted)는 제외
        $hotPosts = Post::with([
                'user:id,nickname,political_type,level',
                'board:id,name,slug,board_type',
            ])
            ->whereHas('board', fn ($q) => $q->whereIn('board_type', ['battle', 'playground']))
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('vote_up_count')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // ⚔️ 전쟁터 최신글
        $battlePosts = Post::with([
                'user:id,nickname,political_type,level',
                'board:id,name,slug,board_type',
            ])
            ->whereHas('board', fn ($q) => $q->where('board_type', 'battle'))
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // 🎡 놀이터 최신글
        $playPosts = Post::with([
                'user:id,nickname,political_type,level',
                'board:id,name,slug,board_type',
            ])
            ->whereHas('board', fn ($q) => $q->where('board_type', 'playground'))
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // 📢 공지사항
        $notices = Post::with(['board:id,name,slug'])
            ->whereHas('board', fn ($q) => $q->where('board_type', 'notice'))
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        // 게시판 목록 (빠른 바로가기 — 전쟁터·놀이터만)
        $boards = Board::where('is_active', true)
            ->whereIn('board_type', ['battle', 'playground'])
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'board_type'])
            ->map(fn ($b) => [
                'id'         => $b->id,
                'name'       => $b->name,
                'slug'       => $b->slug,
                'board_type' => $b->board_type instanceof BoardType ? $b->board_type->value : $b->board_type,
            ])
            ->toArray();

        return Inertia::render('Home', [
            'hotPosts'    => $this->formatPosts($hotPosts),
            'battlePosts' => $this->formatPosts($battlePosts),
            'playPosts'   => $this->formatPosts($playPosts),
            'notices'     => $this->formatPosts($notices),
            'boards'      => $boards,
        ]);
    }

    /** Post 컬렉션을 Vue 친화적 배열로 변환 */
    private function formatPosts(\Illuminate\Database\Eloquent\Collection $posts): array
    {
        return $posts->map(function (Post $p): array {
            $boardType = $p->board?->board_type;
            if ($boardType instanceof BoardType) {
                $boardType = $boardType->value;
            }

            $faction = $p->faction instanceof FactionType ? $p->faction->value : $p->faction;

            $userPoliticalType = $p->user?->political_type;
            if ($userPoliticalType instanceof FactionType) {
                $userPoliticalType = $userPoliticalType->value;
            }

            return [
                'id'            => $p->id,
                'title'         => $p->title,
                'board_slug'    => $p->board?->slug,
                'board_name'    => $p->board?->name,
                'board_type'    => $boardType,
                'faction'       => $faction,
                'is_anonymous'  => (bool) $p->is_anonymous,
                'vote_up_count' => (int) ($p->vote_up_count  ?? 0),
                'view_count'    => (int) ($p->view_count     ?? 0),
                'comment_count' => (int) ($p->comment_count  ?? 0),
                'is_hot'        => (bool) $p->is_hot,
                'is_notice'     => (bool) $p->is_notice,
                'created_at'    => $p->created_at?->toIso8601String(),
                'user'          => $p->user ? [
                    'id'             => $p->user->id,
                    'nickname'       => $p->user->nickname,
                    'political_type' => $userPoliticalType,
                    'level'          => $p->user->level ?? 1,
                    'level_emoji'    => UserLevelService::LEVELS[$p->user->level ?? 1]['emoji'] ?? '🌱',
                ] : null,
            ];
        })->toArray();
    }
}
