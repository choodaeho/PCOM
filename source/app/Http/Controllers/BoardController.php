<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Poll;
use App\Services\UserLevelService;
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

        // 활성 여론조사 목록 (최근 3개) — 진영별 득표 분포(faction_counts) 포함
        $activePolls = Poll::active()
            ->latest()
            ->take(3)
            ->get()
            ->map(function (Poll $p) {
                $statsByFaction = $p->voteStatsByFaction();

                // 진영별 → [option_id => count] 를 옵션별 → [faction => count] 로 전치
                $factionCountsByOption = [];
                foreach ($statsByFaction as $faction => $counts) {
                    foreach ($counts as $optionId => $cnt) {
                        $factionCountsByOption[$optionId][$faction] = $cnt;
                    }
                }

                $options = collect($p->options)->map(function (array $option) use ($factionCountsByOption) {
                    $option['faction_counts'] = $factionCountsByOption[$option['id']] ?? [];

                    return $option;
                })->values()->toArray();

                return [
                    'id'               => $p->id,
                    'question'         => $p->title,
                    'options'          => $options,
                    'total_vote_count' => $p->total_vote_count,
                    'ends_at'          => $p->ends_at,
                ];
            })
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
     * 3단 필터 + 검색:
     *   type        — 'all'(전체글) | 'hot'(인기글/화제글/베스트)
     *   sort        — 'latest'(최신) | 'popular'(추천순) | 'views'(조회순)
     *   category    — 말머리 필터 ('' = 전체)
     *   faction     — 진영 필터 (전쟁터/놀이터 전용)
     *   q           — 검색어
     *   search_type — 'title'(제목) | 'content'(내용) | 'both'(제목+내용)
     */
    public function show(Request $request, Board $board): Response
    {
        $query = $board->posts()
            ->with(['user:id,nickname,political_type,level'])
            ->where('status', 'published');

        // ── type 필터: 인기글(is_hot) ────────────────────────────────
        $type = $request->get('type', 'all');
        if ($type === 'hot') {
            $query->where('is_hot', true);
        }

        // ── 카테고리 필터 ─────────────────────────────────────────────
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // ── 진영 필터 (전쟁터/놀이터) ────────────────────────────────
        if ($request->filled('faction') && in_array($board->board_type->value, ['battle', 'playground'], true)) {
            $query->where('faction', $request->faction);
        }

        // ── 검색 ─────────────────────────────────────────────────────
        // PostgreSQL ILIKE로 대소문자 무관 검색.
        // content는 Quill HTML이므로 내용 검색 시 HTML 태그도 포함될 수 있으나,
        // 실용적으로는 키워드가 텍스트 노드 안에 있어 충분히 동작함.
        if ($request->filled('q')) {
            $q          = '%' . trim($request->get('q')) . '%';
            $searchType = $request->get('search_type', 'title');

            $query->where(function ($sub) use ($q, $searchType): void {
                if ($searchType === 'content') {
                    $sub->whereRaw("content ilike ?", [$q]);
                } elseif ($searchType === 'both') {
                    $sub->where('title', 'ilike', $q)
                        ->orWhereRaw("content ilike ?", [$q]);
                } else {
                    // 기본: 제목 검색
                    $sub->where('title', 'ilike', $q);
                }
            });
        }

        // ── 정렬 ─────────────────────────────────────────────────────
        $sort = $request->get('sort', 'latest');
        if ($sort === 'popular') {
            $query->orderByDesc('vote_up_count')->orderByDesc('created_at');
        } elseif ($sort === 'views') {
            $query->orderByDesc('view_count')->orderByDesc('created_at');
        } else {
            // 최신순 기본값: 공지글 항상 최상단
            $query->orderByDesc('is_notice')->orderByDesc('created_at');
        }

        $boardType = $board->board_type;

        // 모바일 UA 감지 → 데스크탑 20개 / 모바일 10개
        $ua       = strtolower($request->header('User-Agent', ''));
        $isMobile = (bool) preg_match('/(android|iphone|ipod|ipad|mobile|blackberry|windows phone)/i', $ua);
        $perPage  = $isMobile ? 10 : 20;

        return Inertia::render('Boards/Show', [
            'board'   => array_merge(
                $board->only(['id', 'name', 'slug', 'description']),
                [
                    'board_type'      => $boardType->value,
                    'allowed_faction' => $board->allowed_faction,
                    'categories'      => $board->categories ?? [],
                    'hot_label'       => $boardType->hotLabel(),
                    'hot_threshold'   => $boardType->hotThreshold(),
                ]
            ),
            'posts'   => $query->paginate($perPage)->withQueryString()->through(function ($post) {
                if ($post->user) {
                    $lv = $post->user->level ?? 1;
                    $post->user->level_emoji = UserLevelService::LEVELS[$lv]['emoji'] ?? '🌱';
                }
                return $post;
            }),
            // PHP 빈 배열은 JSON []로 직렬화 → JS에서 [].sort가 함수 참조가 되어
            // currentSort computed가 'latest'를 반환하지 못하는 버그 방지.
            // (object) 캐스팅으로 빈 경우에도 JSON {} 객체로 직렬화되도록 강제.
            'filters' => (object) $request->only(['type', 'sort', 'category', 'faction', 'q', 'search_type']),
        ]);
    }
}
