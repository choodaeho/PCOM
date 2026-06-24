<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Poll;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    /**
     * 게시판 목록 (비로그인 가능).
     *
     * 로그인한 경우 본인 진영 아지트를 단일 객체로 전달.
     * 비로그인 시 azit = null.
     */
    public function index(Request $request): Response
    {
        $user   = $request->user();
        $boards = Board::query()
            ->where('is_active', true)
            ->orderBy('sort_order')          // fix: 컬럼명 sort_order (이전 'order' 오타 수정)
            ->get()
            ->groupBy(fn ($b) => $b->board_type->value);

        $battleBoards     = $boards->get('battle', collect())->values();
        $playgroundBoards = $boards->get('playground', collect())->values();

        // 로그인 사용자의 진영 아지트 (단일 게시판)
        $azit = null;
        if ($user?->political_type !== null) {
            $azit = $boards->get('azit', collect())
                ->first(fn ($b) => $b->allowed_faction === $user->political_type->value);
        }

        // 활성 여론조사 목록 (최근 3개)
        $activePolls = Poll::active()
            ->latest()
            ->take(3)
            ->get()
            ->map(fn ($p) => $p->only(['id', 'question', 'options', 'total_vote_count', 'ends_at']))
            ->values();

        return Inertia::render('Boards/Index', [
            'azit'             => $azit,
            'battleBoards'     => $battleBoards,
            'playgroundBoards' => $playgroundBoards,
            'activePolls'      => $activePolls,
            'userFaction'      => $user?->political_type?->value,
        ]);
    }

    /**
     * 게시판 글 목록 (비로그인 가능).
     *
     * allowed_faction을 Vue에 전달하여 클라이언트가 글쓰기 가능 여부를 판단하도록 함.
     */
    public function show(Request $request, Board $board): Response
    {
        $query = $board->posts()
            ->with(['user:id,nickname,political_type'])
            ->where('status', 'published');

        // 전쟁터/놀이터에서 진영 필터 (선택적)
        if ($request->filled('faction') && in_array($board->board_type->value, ['battle', 'playground'], true)) {
            $query->where('faction', $request->faction);
        }

        $sort = $request->get('sort', 'latest');
        $query->when($sort === 'popular', fn ($q) => $q->orderByDesc('vote_up_count'))
              ->when($sort === 'views',   fn ($q) => $q->orderByDesc('view_count'))
              ->when($sort === 'latest',  fn ($q) => $q->latest());

        return Inertia::render('Boards/Show', [
            'board'   => array_merge(
                $board->only(['id', 'name', 'slug', 'description']),
                [
                    'board_type'      => $board->board_type->value,  // 'azit' | 'battle' | 'playground' | 'notice'
                    'allowed_faction' => $board->allowed_faction,    // fix: plain string, not enum
                ]
            ),
            'posts'   => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['sort', 'faction']),
        ]);
    }
}
