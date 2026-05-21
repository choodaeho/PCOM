<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\VoteType;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    /**
     * POST /api/v1/posts/{post}/vote
     *
     * 게시글 추천/비추천 토글.
     * 같은 vote_type 재요청 시 취소, 다른 vote_type 요청 시 변경.
     * Body: { "vote_type": "up" | "down" }
     */
    public function votePost(Request $request, Post $post): JsonResponse
    {
        abort_if($post->user_id === $request->user()->id, 422, '본인 게시글에는 투표할 수 없습니다.');

        return $this->toggleVote($request, $post);
    }

    /**
     * POST /api/v1/comments/{comment}/vote
     *
     * 댓글 추천/비추천 토글.
     */
    public function voteComment(Request $request, Comment $comment): JsonResponse
    {
        abort_if($comment->user_id === $request->user()->id, 422, '본인 댓글에는 투표할 수 없습니다.');

        return $this->toggleVote($request, $comment);
    }

    // -------------------------------------------------------------------------
    // 내부 헬퍼
    // -------------------------------------------------------------------------

    private function toggleVote(Request $request, Post|Comment $target): JsonResponse
    {
        $validated = $request->validate([
            'vote_type' => ['required', 'in:up,down'],
        ]);

        $voteType = VoteType::from($validated['vote_type']);
        $userId   = $request->user()->id;

        $result = DB::transaction(function () use ($target, $userId, $voteType) {
            $existing = $target->votes()->where('user_id', $userId)->first();

            if ($existing !== null) {
                if ($existing->vote_type === $voteType) {
                    // 같은 유형 → 취소
                    $existing->delete();
                    return ['action' => 'cancelled', 'vote_type' => null];
                }
                // 다른 유형 → 변경 (delete + create로 Observer 정상 동작)
                $existing->delete();
            }

            Vote::create([
                'user_id'      => $userId,
                'votable_id'   => $target->id,
                'votable_type' => get_class($target),
                'vote_type'    => $voteType->value,
            ]);

            return ['action' => 'voted', 'vote_type' => $voteType->value];
        });

        // 최신 카운터 반환
        $target->refresh();

        return response()->json([
            'action'         => $result['action'],
            'vote_type'      => $result['vote_type'],
            'vote_up_count'  => $target->vote_up_count,
            'vote_down_count'=> $target->vote_down_count,
        ]);
    }
}
