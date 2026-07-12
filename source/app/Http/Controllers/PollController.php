<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\PollVoteUpdated;
use App\Models\Poll;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function active(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Poll::active()->latest()->first());
    }

    public function vote(Request $request, Poll $poll): RedirectResponse
    {
        abort_if(!$poll->isOngoing(), 422, '종료된 투표입니다.');

        $validated = $request->validate(['option_id' => ['required', 'integer']]);
        $optionIds = collect($poll->options)->pluck('id')->toArray();

        abort_if(!in_array($validated['option_id'], $optionIds, true), 422, '잘못된 옵션입니다.');

        $existing = $poll->pollVotes()->where('user_id', $request->user()->id)->exists();
        abort_if($existing, 422, '이미 투표했습니다.');

        DB::transaction(function () use ($request, $poll, $validated) {
            $poll->pollVotes()->create([
                'user_id'   => $request->user()->id,
                'option_id' => $validated['option_id'],
                'faction'   => $request->user()->political_type->value,
            ]);

            $options = collect($poll->options)->map(function ($opt) use ($validated) {
                if ($opt['id'] === $validated['option_id']) {
                    $opt['vote_count'] = ($opt['vote_count'] ?? 0) + 1;
                }

                return $opt;
            })->toArray();

            $poll->update([
                'options'          => $options,
                'total_vote_count' => $poll->total_vote_count + 1,
            ]);
        });

        broadcast(new PollVoteUpdated($poll->fresh()));

        return back()->with('success', '투표가 완료되었습니다.');
    }
}
