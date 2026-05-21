<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function votePost(Request $request, Post $post): RedirectResponse
    {
        $this->toggleVote($request, $post);

        return back();
    }

    public function voteComment(Request $request, Comment $comment): RedirectResponse
    {
        $this->toggleVote($request, $comment);

        return back();
    }

    private function toggleVote(Request $request, mixed $votable): void
    {
        $validated = $request->validate(['vote_type' => ['required', 'in:up,down']]);

        abort_if($votable->user_id === $request->user()->id, 422, '본인 게시물에 투표할 수 없습니다.');

        DB::transaction(function () use ($request, $votable, $validated) {
            $existing = $votable->votes()->where('user_id', $request->user()->id)->first();

            if ($existing) {
                if ($existing->vote_type === $validated['vote_type']) {
                    $existing->delete();
                } else {
                    $existing->update(['vote_type' => $validated['vote_type']]);
                }
            } else {
                $votable->votes()->create([
                    'user_id'   => $request->user()->id,
                    'vote_type' => $validated['vote_type'],
                ]);
            }
        });
    }
}
