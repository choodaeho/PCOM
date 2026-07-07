<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;

class NotificationService
{
    /**
     * 내 게시글에 새 댓글이 달렸을 때 게시글 작성자에게 알림.
     */
    public function notifyComment(Post $post, Comment $comment, User $commenter): void
    {
        // 자신의 게시글에 본인이 댓글 달면 알림 없음
        if ($post->user_id === $commenter->id) {
            return;
        }

        // 게시글 작성자 로드
        $post->loadMissing('user', 'board');
        $recipient = $post->user;

        if (! $recipient) {
            return;
        }

        $boardSlug = $post->board?->slug ?? '';

        Notification::create([
            'user_id' => $recipient->id,
            'type'    => 'comment',
            'title'   => '새 댓글이 달렸습니다',
            'message' => "{$commenter->nickname}님이 \"{$this->truncate($post->title, 40)}\"에 댓글을 남겼습니다.",
            'url'     => "/boards/{$boardSlug}/posts/{$post->id}#comment-{$comment->id}",
            'data'    => [
                'post_id'          => $post->id,
                'comment_id'       => $comment->id,
                'board_slug'       => $boardSlug,
                'actor_nickname'   => $commenter->nickname,
                'actor_faction'    => $commenter->political_type?->value,
            ],
        ]);
    }

    /**
     * 내 댓글에 답글이 달렸을 때 부모 댓글 작성자에게 알림.
     */
    public function notifyReply(Comment $parentComment, Post $post, Comment $reply, User $replier): void
    {
        // 본인 댓글에 본인이 답글 달면 알림 없음
        if ($parentComment->user_id === $replier->id) {
            return;
        }

        $parentComment->loadMissing('user');
        $recipient = $parentComment->user;

        if (! $recipient) {
            return;
        }

        $post->loadMissing('board');
        $boardSlug = $post->board?->slug ?? '';

        Notification::create([
            'user_id' => $recipient->id,
            'type'    => 'reply',
            'title'   => '내 댓글에 답글이 달렸습니다',
            'message' => "{$replier->nickname}님이 내 댓글에 답글을 남겼습니다.",
            'url'     => "/boards/{$boardSlug}/posts/{$post->id}#comment-{$reply->id}",
            'data'    => [
                'post_id'          => $post->id,
                'comment_id'       => $reply->id,
                'board_slug'       => $boardSlug,
                'actor_nickname'   => $replier->nickname,
                'actor_faction'    => $replier->political_type?->value,
            ],
        ]);
    }

    /**
     * 내 게시글이 인기글로 등재됐을 때 게시글 작성자에게 알림.
     */
    public function notifyHotPost(Post $post): void
    {
        $post->loadMissing('user', 'board');
        $recipient = $post->user;

        if (! $recipient) {
            return;
        }

        $boardSlug = $post->board?->slug ?? '';

        Notification::create([
            'user_id' => $recipient->id,
            'type'    => 'hot',
            'title'   => '내 게시글이 인기글이 됐습니다! 🔥',
            'message' => "\"{$this->truncate($post->title, 50)}\"이(가) 인기글로 선정됐습니다.",
            'url'     => "/boards/{$boardSlug}/posts/{$post->id}",
            'data'    => [
                'post_id'    => $post->id,
                'board_slug' => $boardSlug,
            ],
        ]);
    }

    /**
     * 문자열을 최대 길이로 잘라 … 붙임.
     */
    private function truncate(string $str, int $max): string
    {
        return mb_strlen($str) > $max
            ? mb_substr($str, 0, $max) . '…'
            : $str;
    }
}
