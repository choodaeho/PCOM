<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Comment;
use App\Models\Post;
use App\Services\NotificationService;
use App\Services\UserLevelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private readonly UserLevelService    $levelService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function store(Request $request, Board $board, Post $post): RedirectResponse
    {
        $validated = $request->validate([
            'content'     => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id'   => ['nullable', 'integer', 'exists:comments,id'],
            'reply_to_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $parentId  = null;
        $replyToId = null;

        if ($rawParentId = ($validated['parent_id'] ?? null)) {
            $target = Comment::findOrFail($rawParentId);
            abort_if($target->post_id !== $post->id, 422, '다른 게시글의 댓글입니다.');

            if ($target->parent_id !== null) {
                // 답글의 답글 -> parent를 root 댓글로 올려서 depth 1 유지
                $parentId  = $target->parent_id;
                $replyToId = $target->id;           // @닉네임 표시용
            } else {
                $parentId  = $target->id;
                $replyToId = $validated['reply_to_id'] ?? null;
            }
        }

        // reply_to_id 유효성 재확인 (같은 스레드인지)
        if ($replyToId !== null) {
            $replyTarget = Comment::find($replyToId);
            if (! $replyTarget || $replyTarget->post_id !== $post->id) {
                $replyToId = null;
            }
        }

        $comment = $post->comments()->create([
            'user_id'      => $request->user()->id,
            'faction'      => $request->user()->political_type->value,
            'content'      => $validated['content'],
            'parent_id'    => $parentId,
            'reply_to_id'  => $replyToId,
            'is_anonymous' => false,
        ]);

        // 알림 발송
        if ($parentId === null) {
            // 새 댓글 -> 게시글 작성자에게 알림
            $this->notificationService->notifyComment($post, $comment, $request->user());
        } else {
            // 답글 -> root 댓글 작성자에게 알림
            $parentComment = Comment::find($parentId);
            if ($parentComment) {
                $this->notificationService->notifyReply($parentComment, $post, $comment, $request->user());
            }

            // 재답글(@멘션 대상): root와 다른 유저면 추가 알림
            if ($replyToId !== null && $replyToId !== $parentId) {
                $replyToComment = Comment::find($replyToId);
                if ($replyToComment && $replyToComment->user_id !== ($parentComment?->user_id ?? -1)) {
                    $this->notificationService->notifyReply($replyToComment, $post, $comment, $request->user());
                }
            }
        }

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
