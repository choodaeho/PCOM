<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function reportPost(Request $request, Post $post): RedirectResponse
    {
        abort_if($post->user_id === $request->user()->id, 422, '본인 게시글은 신고할 수 없습니다.');
        $this->createReport($request, $post);

        return back()->with('success', '신고가 접수되었습니다.');
    }

    public function reportComment(Request $request, Comment $comment): RedirectResponse
    {
        abort_if($comment->user_id === $request->user()->id, 422);
        $this->createReport($request, $comment);

        return back()->with('success', '신고가 접수되었습니다.');
    }

    private function createReport(Request $request, mixed $reportable): void
    {
        $validated = $request->validate([
            'reason' => ['required', 'in:hate_speech,misinformation,spam,obscene,other'],
            'detail' => ['nullable', 'string', 'max:500'],
        ]);

        $existing = $reportable->reports()->where('reporter_id', $request->user()->id)->first();
        abort_if($existing !== null, 422, '이미 신고한 콘텐츠입니다.');

        $reportable->reports()->create([
            'reporter_id' => $request->user()->id,
            'reason'      => $validated['reason'],
            'detail'      => $validated['detail'] ?? null,
        ]);

        $reportable->increment('report_count');

        // 신고 누적 10건 이상 → 자동 블라인드
        if ($reportable->report_count >= 10 && $reportable->status === PostStatus::Published) {
            $reportable->update(['status' => PostStatus::Hidden]);
        }
    }
}
