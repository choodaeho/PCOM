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
     * 로그인한 경우 본인 진영 아지트를 상단에 강조 표시.
     * 비로그인 시 모든 게시판 노출 (읽기만 가능).
     */
    public function index(Request $request): Response
    {
        $user   = $request->user();
        $boards = Board::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->groupBy(fn ($b) => $b->board_type->value);

        $azitBoards   = $boards->get('azit', collect())->values();
        $battleBoards = $boards->get('battle', collect())->values();
        $noticeBoards = $boards->get('notice', collect())->values();

        return Inertia::render('Boards/Index', [
            'azitBoards'    => $azitBoards,
            'battleBoards'  => $battleBoards,
            'noticeBoards'  => $noticeBoards,
            // 로그인 사용자의 진영을 Vue에서 강조 처리에 활용
            'userFaction'   => $user?->political_type?->value,
            'activePoll'    => Poll::active()->latest()->first()?->only([
                'id', 'title', 'options', 'total_vote_count', 'ends_at',
            ]),
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

        // 전쟁터에서 진영 필터
        if ($request->filled('faction') && $board->board_type->value === 'battle') {
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
                    'board_type'      => $board->board_type->value,        // 'azit' | 'battle' | 'notice'
                    'allowed_faction' => $board->allowed_faction?->value,  // 'conservative' | 'moderate' | 'progressive' | null
                ]
            ),
            'posts'   => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['sort', 'faction']),
        ]);
    }
}
