<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function create(Request $request, Board $board): Response
    {
        return Inertia::render('Posts/Create', [
            'board' => $board->only(['id', 'name', 'slug', 'board_type']),
        ]);
    }

    public function store(Request $request, Board $board): mixed
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'min:2', 'max:300'],
            'content'      => ['required', 'string', 'min:10'],
            'is_anonymous' => ['boolean'],
        ]);

        $post = $board->posts()->create([
            'user_id'      => $request->user()->id,
            'faction'      => $request->user()->political_type->value,
            'title'        => $validated['title'],
            'content'      => $validated['content'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
            'status'       => 'published',
        ]);

        return redirect()->route('posts.show', [$board->slug, $post])
            ->with('success', '게시글이 작성되었습니다.');
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
            'user:id,nickname,political_type',
            'comments.user:id,nickname,political_type',
            'comments.replies.user:id,nickname,political_type',
        ]);

        // 비로그인이면 myVote = null
        $myVote = $user?->votes()
            ->where('votable_type', Post::class)
            ->where('votable_id', $post->id)
            ->value('vote_type');

        return Inertia::render('Posts/Show', [
            'board'  => array_merge(
                $board->only(['id', 'name', 'slug']),
                ['board_type' => $board->board_type->value]
            ),
            'post'   => $post,
            'myVote' => $myVote,
        ]);
    }

    public function edit(Request $request, Board $board, Post $post): Response
    {
        abort_if($post->user_id !== $request->user()->id, 403);

        return Inertia::render('Posts/Edit', [
            'board' => $board->only(['id', 'name', 'slug']),
            'post'  => $post->only(['id', 'title', 'content', 'is_anonymous']),
        ]);
    }

    public function update(Request $request, Board $board, Post $post): mixed
    {
        abort_if($post->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title'   => ['required', 'string', 'min:2', 'max:300'],
            'content' => ['required', 'string', 'min:10'],
        ]);

        $post->update($validated);

        return redirect()->route('posts.show', [$board->slug, $post])
            ->with('success', '게시글이 수정되었습니다.');
    }

    public function destroy(Request $request, Board $board, Post $post): mixed
    {
        abort_if($post->user_id !== $request->user()->id && ! $request->user()->is_admin, 403);

        $post->delete();

        return redirect()->route('boards.show', $board->slug)
            ->with('success', '게시글이 삭제되었습니다.');
    }
}
