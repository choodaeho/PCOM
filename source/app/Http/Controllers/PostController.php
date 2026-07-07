<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Comment;
use App\Models\Post;
use App\Services\UserLevelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(private readonly UserLevelService $levelService)
    {
    }

    public function create(Request $request, Board $board): Response
    {
        return Inertia::render('Posts/Create', [
            'board' => array_merge(
                $board->only(['id', 'name', 'slug', 'board_type']),
                ['categories' => $board->categories ?? []]
            ),
        ]);
    }

    public function store(Request $request, Board $board): mixed
    {
        $validated = $request->validate([
            'title'    => ['required', 'string', 'min:2', 'max:300'],
            'content'  => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        // HTML 태그를 제거한 실제 텍스트 길이 검증
        $plainText = trim(strip_tags($validated['content']));
        if (mb_strlen($plainText) < 2) {
            return back()->withErrors(['content' => '본문 내용을 2자 이상 입력해주세요.'])->withInput();
        }

        $post = $board->posts()->create([
            'user_id'      => $request->user()->id,
            'faction'      => $request->user()->political_type->value,
            'title'        => $validated['title'],
            'content'      => $validated['content'],
            'category'     => $validated['category'] ?? null,
            'is_anonymous' => false,
            'status'       => 'published',
        ]);

        $this->levelService->syncUser($request->user());

        // back_to_board: 상세보기의 "목록으로"가 글작성 페이지 대신 게시판 목록으로 이동하도록
        return redirect()->route('posts.show', [$board->slug, $post])
            ->with('success', '게시글이 작성되었습니다.')
            ->with('back_to_board', true);
    }

    /**
     * 게시글 상세 (비로그인 가능).
     *
     * - 비로그인: myVote = null, 추천 버튼 클릭 시 Vue에서 로그인 페이지로 리디렉트
     * - 조회수: 비로그인 or 타인이 볼 때만 증가
     */
    public function show(Request $request, Board $board, Post $post): Response
    {
        $user = $request->user();

        // 본인이 아닌 경우(비로그인 포함) 조회수 증가
        if ($user === null || $post->user_id !== $user->id) {
            $post->incrementViewCount();
        }

        $post->load([
            'user:id,nickname,political_type,level',
            'comments.user:id,nickname,political_type,level',
            'comments.replies.user:id,nickname,political_type,level',
            // reply_to_id → @닉네임 표시용 (답글의 답글)
            'comments.replies.replyTo:id,user_id',
            'comments.replies.replyTo.user:id,nickname',
        ]);

        // level_emoji 는 DB 컬럼이 아닌 LEVELS 상수 조회값 — BoardController 방식으로 주입
        $appendLevelEmoji = function ($user): void {
            if ($user) {
                $lv = $user->level ?? 1;
                $user->level_emoji = UserLevelService::LEVELS[$lv]['emoji'] ?? '🌱';
            }
        };

        $appendLevelEmoji($post->user);
        foreach ($post->comments as $comment) {
            $appendLevelEmoji($comment->user);
            foreach ($comment->replies as $reply) {
                $appendLevelEmoji($reply->user);
                // replyTo.user 는 닉네임만 필요, level_emoji 불필요
            }
        }

        // 비로그인이면 myVote = null
        $myVote = $user?->votes()
            ->where('votable_type', Post::class)
            ->where('votable_id', $post->id)
            ->value('vote_type');

        // 댓글/답글 투표 상태 (이미 로드된 관계에서 ID 수집 → 추가 쿼리 최소화)
        $myCommentVotes = [];
        if ($user) {
            $commentIds = $post->comments->flatMap(
                fn($c) => collect([$c->id])->merge($c->replies->pluck('id'))
            )->filter();

            if ($commentIds->isNotEmpty()) {
                $myCommentVotes = $user->votes()
                    ->where('votable_type', Comment::class)
                    ->whereIn('votable_id', $commentIds)
                    ->get(['votable_id', 'vote_type'])
                    ->mapWithKeys(fn($v) => [(string) $v->votable_id => $v->vote_type->value])
                    ->toArray();
            }
        }

        // ── 하단 글 목록 (펨코 스타일: 현재 글 전후 각 5개) ──────────
        $toRow = fn (mixed $p, bool $current = false): array => [
            'id'            => $p->id,
            'title'         => $p->title,
            'comment_count' => (int) ($p->comment_count ?? 0),
            'view_count'    => (int) ($p->view_count    ?? 0),
            'is_hot'        => (bool) $p->is_hot,
            'is_notice'     => (bool) $p->is_notice,
            'created_at'    => $p->created_at?->toIso8601String(),
            'author'        => $p->user?->nickname ?? '알 수 없음',
            'is_current'    => $current,
        ];

        // 현재 글보다 ID 큰 글 5개 (최신 → 오래된 순 정렬)
        $newerPosts = $board->posts()
            ->with('user:id,nickname')
            ->where('status', 'published')
            ->where('id', '>', $post->id)
            ->orderBy('id', 'asc')
            ->take(5)
            ->get()
            ->sortByDesc('id')
            ->values();

        // 현재 글보다 ID 작은 글 5개 (최신 → 오래된 순)
        $olderPosts = $board->posts()
            ->with('user:id,nickname')
            ->where('status', 'published')
            ->where('id', '<', $post->id)
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Eloquent\Collection::map()은 여전히 Eloquent Collection을 반환해
        // merge() 시 getKey()를 배열에 호출하는 버그 발생.
        // toBase()로 일반 Illuminate\Support\Collection으로 변환 후 조합.
        $boardPosts = $newerPosts->toBase()
            ->map(fn ($p) => $toRow($p))
            ->push($toRow($post, true))
            ->merge($olderPosts->toBase()->map(fn ($p) => $toRow($p)))
            ->values();

        return Inertia::render('Posts/Show', [
            'board'          => array_merge(
                $board->only(['id', 'name', 'slug']),
                ['board_type' => $board->board_type->value]
            ),
            'post'           => $post,
            'myVote'         => $myVote,
            'myCommentVotes' => $myCommentVotes,  // { "commentId": "up"|"down" }
            'boardPosts'     => $boardPosts,
            // store/update 리다이렉트 후 1회만 true → "목록으로"가 게시판 루트로 이동
            'backToBoard'    => (bool) session('back_to_board', false),
        ]);
    }

    public function edit(Request $request, Board $board, Post $post): Response
    {
        abort_if($post->user_id !== $request->user()->id, 403);

        return Inertia::render('Posts/Edit', [
            'board' => array_merge(
                $board->only(['id', 'name', 'slug']),
                ['categories' => $board->categories ?? []]
            ),
            'post'  => $post->only(['id', 'title', 'content', 'category']),
        ]);
    }

    public function update(Request $request, Board $board, Post $post): mixed
    {
        abort_if($post->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title'    => ['required', 'string', 'min:2', 'max:300'],
            'content'  => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        $plainText = trim(strip_tags($validated['content']));
        if (mb_strlen($plainText) < 2) {
            return back()->withErrors(['content' => '본문 내용을 2자 이상 입력해주세요.'])->withInput();
        }

        $post->update($validated);

        // back_to_board: 수정 후에도 "목록으로"가 편집 페이지 대신 게시판 목록으로 이동하도록
        return redirect()->route('posts.show', [$board->slug, $post])
            ->with('success', '게시글이 수정되었습니다.')
            ->with('back_to_board', true);
    }

    public function destroy(Request $request, Board $board, Post $post): mixed
    {
        abort_if($post->user_id !== $request->user()->id && ! $request->user()->is_admin, 403);

        $post->delete();

        return redirect()->route('boards.show', $board->slug)
            ->with('success', '게시글이 삭제되었습니다.');
    }
}
