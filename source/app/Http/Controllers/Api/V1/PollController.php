<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\PollVoteUpdated;
use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    /**
     * GET /api/v1/polls/active
     *
     * 현재 진행 중인 투표와 진영별 현황 반환.
     */
    public function active(Request $request): JsonResponse
    {
        $poll = Poll::active()->latest()->first();

        if ($poll === null) {
            return response()->json(['poll' => null]);
        }

        $myVote = $request->user()
            ? PollVote::where('poll_id', $poll->id)->where('user_id', $request->user()->id)->first()
            : null;

        return response()->json([
            'poll'        => $poll,
            'my_option'   => $myVote?->option_id,
            'stats'       => $poll->voteStatsByFaction(),
        ]);
    }

    /**
     * POST /api/v1/polls/{poll}/vote
     *
     * 투표 참여. 한 번만 가능 (변경 불가).
     * Body: { "option_id": 1 }
     */
    public function vote(Request $request, Poll $poll): JsonResponse
    {
        abort_unless($poll->isOngoing(), 422, '진행 중인 투표가 아닙니다.');

        $validated = $request->validate([
            'option_id' => ['required', 'integer'],
        ]);

        // 유효한 option_id인지 확인
        $validOptions = array_column($poll->options, 'id');
        abort_unless(in_array($validated['option_id'], $validOptions), 422, '유효하지 않은 선택지입니다.');

        $user    = $request->user();
        $existed = PollVote::where('poll_id', $poll->id)->where('user_id', $user->id)->exists();

        if ($existed) {
            return response()->json(['message' => '이미 투표에 참여했습니다.'], 422);
        }

        DB::transaction(function () use ($poll, $user, $validated) {
            PollVote::create([
                'poll_id'   => $poll->id,
                'user_id'   => $user->id,
                'option_id' => $validated['option_id'],
                'faction'   => $user->political_type->value,
            ]);

            // 비정규화 카운터 갱신
            $poll->increment('total_vote_count');

            // options JSONB의 해당 option vote_count 증가
            $options = $poll->options;
            foreach ($options as &$option) {
                if ($option['id'] === $validated['option_id']) {
                    $option['vote_count'] = ($option['vote_count'] ?? 0) + 1;
                    break;
                }
            }
            unset($option);
            $poll->update(['options' => $options]);
        });

        broadcast(new PollVoteUpdated($poll->fresh()));

        return response()->json([
            'message' => '투표가 완료되었습니다.',
            'stats'   => $poll->fresh()->voteStatsByFaction(),
        ]);
    }

    /**
     * GET /api/v1/polls/{poll}/stats
     *
     * 진영별 투표 현황 집계.
     */
    public function stats(Poll $poll): JsonResponse
    {
        return response()->json([
            'poll'  => $poll->only(['id', 'title', 'options', 'total_vote_count']),
            'stats' => $poll->voteStatsByFaction(),
        ]);
    }
}
