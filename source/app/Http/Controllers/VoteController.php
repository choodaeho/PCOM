<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\VoteType;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Vote;
use App\Services\NotificationService;
use App\Services\UserLevelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function __construct(
        private readonly UserLevelService    $levelService,
        private readonly NotificationService $notificationService,
    ) {
    }

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

    /**
     * 진영 관계에 따른 매너 점수 증감량.
     * 추천: 다른 진영 +1 / 같은 진영 0
     * 비추천: 같은 진영 -1 / 다른 진영 0
     */
    private function mannerDelta(VoteType $voteType, string $voter, string $author): int
    {
        $same = $voter === $author;
        return match($voteType) {
            VoteType::Up   => $same ? 0 : 1,
            VoteType::Down => $same ? -1 : 0,
        };
    }

    private function factionValue(mixed $political_type): string
    {
        return $political_type instanceof \App\Enums\FactionType
            ? $political_type->value
            : (string) $political_type;
    }

    private function toggleVote(Request $request, mixed $votable): void
    {
        $validated = $request->validate(['vote_type' => ['required', 'in:up,down']]);

        abort_if($votable->user_id === $request->user()->id, 422, '본인 게시물에 투표할 수 없습니다.');

        $voteType     = VoteType::from($validated['vote_type']);
        $voterFaction = $this->factionValue($request->user()->political_type);

        $author  = null;
        $wasHot  = $votable instanceof Post ? (bool) $votable->is_hot : false;

        DB::transaction(function () use ($request, $votable, $voteType, $voterFaction, &$author) {
            $existing      = $votable->votes()->where('user_id', $request->user()->id)->first();
            $author        = $votable->user;
            $authorFaction = $author ? $this->factionValue($author->political_type) : '';

            if ($existing) {
                $oldType = $existing->vote_type;

                if ($existing->vote_type === $voteType) {
                    // 같은 타입 재클릭 -> 취소 (이전 효과 되돌리기)
                    $existing->delete();
                    if ($author) {
                        $delta = $this->mannerDelta($voteType, $voterFaction, $authorFaction);
                        if ($delta !== 0) {
                            $author->increment('manner_score', -$delta);
                        }
                    }
                } else {
                    // 다른 타입으로 변경 -> 이전 효과 되돌리고 새 효과 적용
                    $existing->update(['vote_type' => $voteType]);
                    if ($author) {
                        $reverseDelta = $this->mannerDelta($oldType, $voterFaction, $authorFaction);
                        $newDelta     = $this->mannerDelta($voteType, $voterFaction, $authorFaction);
                        $total        = $newDelta - $reverseDelta;
                        if ($total !== 0) {
                            $author->increment('manner_score', $total);
                        }
                    }
                }
            } else {
                // 신규 투표
                $votable->votes()->create([
                    'user_id'   => $request->user()->id,
                    'vote_type' => $voteType,
                ]);
                if ($author) {
                    $delta = $this->mannerDelta($voteType, $voterFaction, $authorFaction);
                    if ($delta !== 0) {
                        $author->increment('manner_score', $delta);
                    }
                }
            }

            // 비정규화 카운터 동기화
            $votable->update([
                'vote_up_count'   => $votable->votes()->where('vote_type', VoteType::Up->value)->count(),
                'vote_down_count' => $votable->votes()->where('vote_type', VoteType::Down->value)->count(),
            ]);
            $votable->refresh();

            // 인기글 자동 등재: Post + 추천 threshold 초과 + 아직 미등재 상태
            // FM코리아 방식: 한번 등재되면 추천 감소해도 유지 (is_hot = false 로 돌리지 않음)
            if ($votable instanceof Post && ! $votable->is_hot) {
                $votable->loadMissing('board');
                $threshold = $votable->board?->board_type?->hotThreshold() ?? 5;
                if ($votable->vote_up_count >= $threshold) {
                    Post::where('id', $votable->id)->update(['is_hot' => true]);
                    $votable->is_hot = true;
                }
            }
        });

        // 인기글 등재 알림 (트랜잭션 밖에서 발송 — 중복 방지)
        if ($votable instanceof Post && $votable->is_hot && ! $wasHot) {
            $alreadyNotified = Notification::where('user_id', $votable->user_id)
                ->where('type', 'hot')
                ->whereRaw("data->>'post_id' = ?", [(string) $votable->id])
                ->exists();
            if (! $alreadyNotified) {
                $this->notificationService->notifyHotPost($votable);
            }
        }

        // 트랜잭션 완료 후 게시물/댓글 작성자의 XP 동기화
        if ($author !== null) {
            $author->refresh();
            $this->levelService->syncUser($author);
        }
    }
}
