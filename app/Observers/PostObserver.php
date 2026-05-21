<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Board;
use App\Models\Post;

/**
 * Post 모델 Observer.
 *
 * 게시물 생성/삭제 시 boards.post_count 비정규화 카운터를 자동 동기화.
 * 직접 COUNT 쿼리를 날리지 않아 조회 성능을 보전.
 */
class PostObserver
{
    /**
     * 게시물 생성 후 → 게시판 카운터 +1.
     */
    public function created(Post $post): void
    {
        Board::where('id', $post->board_id)
            ->increment('post_count');
    }

    /**
     * 소프트 삭제 후 → 게시판 카운터 -1.
     */
    public function deleted(Post $post): void
    {
        Board::where('id', $post->board_id)
            ->decrement('post_count');
    }

    /**
     * 복구 후 → 게시판 카운터 +1.
     */
    public function restored(Post $post): void
    {
        Board::where('id', $post->board_id)
            ->increment('post_count');
    }
}
