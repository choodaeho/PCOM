<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Comment;
use App\Models\Post;
use App\Services\UserLevelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(private readonly UserLevelService $levelService)
    {
    }

    public function store(Request $request, Board $board, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'content'      => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id'    => ['nullable', 'integer', 'exists:comments,id'],
            'is_anonymous' => ['boolean'],
        ]);

        if ($validated['parent_id'] ?? false) {
            $parent = Comment::find($validated['parent_id']);
            abort_if($parent->post_id !== $post->id || $parent->parent_id !== null, 422);
        }

        $post->comments()->create([
            'user_id'      => $request->user()->id,
            'faction'      => $request->user()->political_type->value,
            'content'      => $validated['content'],
            'parent_id'    => $validated['parent_id'] ?? null,
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);

        $this->levelService->syncUser($request->user());

        return back()->with('success', '댓글이 작성되었습니다.');
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        abort_if($comment->user_id !== $request->user()->id, 403);

        $validated = $request->validate(['content' => ['required', 'string', 'min:2', 'max:2000']]);

        $comment->update($validated);

        return back()->with('success', '댓글이 수정되었습니다.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        abort_if($comment->user_id !== $request->user()->id && !$request->user()->is_admin, 403);

        $comment->delete();

        return back()->with('success', '댓글이 삭제되었습니다.');
    }
}
