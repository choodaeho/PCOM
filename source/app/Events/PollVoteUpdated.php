<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Poll;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 실시간 투표(The Poll) 결과 갱신 브로드캐스트.
 *
 * routes/channels.php의 polls.{pollId} 공개 채널로 전송.
 * 프론트 Boards/Index.vue가 .PollVoteUpdated 이벤트를 구독해
 * 페이지 새로고침 없이 진영별 표 차이를 실시간 반영.
 */
class PollVoteUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Poll $poll)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('polls.' . $this->poll->id);
    }

    public function broadcastAs(): string
    {
        return 'PollVoteUpdated';
    }

    /**
     * @return array{poll_id: int, options: array<int, array<string, mixed>>, total_vote_count: int}
     */
    public function broadcastWith(): array
    {
        // 진영별 → [option_id => count] 를 옵션별 → [faction => count] 로 전치
        $statsByFaction = $this->poll->voteStatsByFaction();
        $factionCountsByOption = [];
        foreach ($statsByFaction as $faction => $counts) {
            foreach ($counts as $optionId => $cnt) {
                $factionCountsByOption[$optionId][$faction] = $cnt;
            }
        }

        $options = collect($this->poll->options)->map(function (array $option) use ($factionCountsByOption) {
            $option['faction_counts'] = $factionCountsByOption[$option['id']] ?? [];

            return $option;
        })->values()->toArray();

        return [
            'poll_id'          => $this->poll->id,
            'options'          => $options,
            'total_vote_count' => $this->poll->total_vote_count,
        ];
    }
}
