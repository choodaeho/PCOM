<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Poll;
use App\Services\FactionScoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function index(Request $request): Response
    {
        $user   = $request->user();
        $boards = Board::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->groupBy(fn ($b) => $b->board_type->value);

        return Inertia::render('Boards/Index', [
            'azitBoards'   => $boards->get('azit', collect())
                ->filter(fn ($b) => $user->canAccessBoard($b))->values(),
            'battleBoards' => $boards->get('battle', collect())->values(),
            'noticeBoards' => $boards->get('notice', collect())->values(),
            'activePoll'   => Poll::active()->latest()->first()?->only(['id', 'title', 'options', 'total_vote_count', 'ends_at']),
        ]);
    }

    public function show(Request $request, Board $board): Response
    {
        $user = $request->user();

        if (!$user->canAccessBoard($board)) {
            abort(403, '이 게시판에 접근할 권한이 없습니다.');
        }

        $query = $board->posts()->with(['user:id,nickname,political_type'])
            ->where('status', 'published');

        if ($request->filled('faction') && $board->board_type->value === 'battle') {
            $query->where('faction', $request->faction);
        }

        $sort = $request->get('sort', 'latest');
        $query->when($sort === 'popular', fn ($q) => $q->orderByDesc('vote_up_count'))
              ->when($sort === 'views',   fn ($q) => $q->orderByDesc('view_count'))
              ->when($sort === 'latest',  fn ($q) => $q->latest());

        return Inertia::render('Boards/Show', [
            'board'   => $board->only(['id', 'name', 'slug', 'board_type', 'description']),
            'posts'   => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['sort', 'faction']),
        ]);
    }
}
