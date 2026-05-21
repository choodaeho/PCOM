<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    /**
     * GET /api/v1/posts/{post}/comments
     *
     * 게시글 댓글 목록 (최상위 댓글 + 대댓글 eager loading).
     */
    public function index(Post $post): JsonResponse
    {
        $comments = $post->comments()
            ->published()
            ->with([
                'user:id,nickname,political_type,avatar_url',
                'replies' => fn ($q) => $q->published()
                    ->with('user:id,nickname,political_type,avatar_url')
                    ->orderBy('created_at'),
            ])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'comments' => $comments,
            'total'    => $post->comment_count,
        ]);
    }

    /**
     * POST /api/v1/posts/{post}/comments
     *
     * 댓글 또는 대댓글 작성.
     * Body: { "content": "...", "parent_id": null|int, "is_anonymous": false }
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'content'      => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id'    => ['nullable', 'integer', 'exists:comments,id'],
            'is_anonymous' => ['boolean'],
        ]);

        // 대댓글인 경우 parent가 같은 게시글 소속인지 확인
        if (isset($validated['parent_id'])) {
            $parent = Comment::findOrFail($validated['parent_id']);
            abort_if($parent->post_id !== $post->id, 422, '잘못된 부모 댓글입니다.');
            abort_if($parent->parent_id !== null, 422, '대댓글에는 답글을 달 수 없습니다.');
        }

        $user    = $request->user();
        $comment = $post->allComments()->create([
            'user_id'      => $user->id,
            'parent_id'    => $validated['parent_id'] ?? null,
            'faction'      => $user->political_type->value,
            'content'      => $validated['content'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
            'status'       => 'published',
        ]);

        return response()->json([
            'message' => '댓글이 등록되었습니다.',
            'comment' => $comment->load('user:id,nickname,political_type'),
        ], 201);
    }

    /**
     * PUT /api/v1/comments/{comment}
     *
     * 댓글 수정 (본인만 가능).
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        abort_if($comment->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => '댓글이 수정되었습니다.',
            'comment' => $comment->fresh(),
        ]);
    }

    /**
     * DELETE /api/v1/comments/{comment}
     *
     * 댓글 삭제 (본인 or 관리자).
     */
    public function destroy(Request $request, Comment $comment): Response
    {
        $user = $request->user();
        abort_if($comment->user_id !== $user->id && ! $user->isAdmin(), 403);

        $comment->delete();

        return response()->noContent();
    }
}
