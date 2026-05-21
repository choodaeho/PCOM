<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Comment;
use App\Models\Post;

/**
 * Comment 모델 Observer.
 *
 * 댓글 생성/삭제 시 posts.comment_count 및
 * 대댓글의 경우 comments.reply_count를 자동 동기화.
 */
class CommentObserver
{
    /**
     * 댓글 생성 후.
     */
    public function created(Comment $comment): void
    {
        // 게시물 댓글 수 증가
        Post::where('id', $comment->post_id)
            ->increment('comment_count');

        // 대댓글인 경우 부모 댓글의 reply_count 증가
        if ($comment->parent_id !== null) {
            Comment::where('id', $comment->parent_id)
                ->increment('reply_count');
        }
    }

    /**
     * 소프트 삭제 후.
     */
    public function deleted(Comment $comment): void
    {
        Post::where('id', $comment->post_id)
            ->decrement('comment_count');

        if ($comment->parent_id !== null) {
            Comment::where('id', $comment->parent_id)
                ->decrement('reply_count');
        }
    }

    /**
     * 복구 후.
     */
    public function restored(Comment $comment): void
    {
        Post::where('id', $comment->post_id)
            ->increment('comment_count');

        if ($comment->parent_id !== null) {
            Comment::where('id', $comment->parent_id)
                ->increment('reply_count');
        }
    }
}
