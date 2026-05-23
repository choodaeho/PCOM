<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\VoteType;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Vote;

/**
 * Vote 모델 Observer.
 *
 * 추천/비추천 생성·삭제 시 대상(Post | Comment)의
 * vote_up_count / vote_down_count를 자동 동기화.
 */
class VoteObserver
{
    public function created(Vote $vote): void
    {
        $this->updateCount($vote, delta: 1);
    }

    public function deleted(Vote $vote): void
    {
        $this->updateCount($vote, delta: -1);
    }

    // -------------------------------------------------------------------------
    // 내부 헬퍼
    // -------------------------------------------------------------------------

    private function updateCount(Vote $vote, int $delta): void
    {
        $column = match($vote->vote_type) {
            VoteType::Up   => 'vote_up_count',
            VoteType::Down => 'vote_down_count',
        };

        match($vote->votable_type) {
            'App\Models\Post'    => Post::where('id', $vote->votable_id)
                ->increment($column, $delta),
            'App\Models\Comment' => Comment::where('id', $vote->votable_id)
                ->increment($column, $delta),
            default              => null,
        };
    }
}
