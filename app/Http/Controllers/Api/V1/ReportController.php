<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * POST /api/v1/posts/{post}/report
     */
    public function reportPost(Request $request, Post $post): JsonResponse
    {
        abort_if($post->user_id === $request->user()->id, 422, '본인 게시글은 신고할 수 없습니다.');
        return $this->createReport($request, $post);
    }

    /**
     * POST /api/v1/comments/{comment}/report
     */
    public function reportComment(Request $request, Comment $comment): JsonResponse
    {
        abort_if($comment->user_id === $request->user()->id, 422, '본인 댓글은 신고할 수 없습니다.');
        return $this->createReport($request, $comment);
    }

    // -------------------------------------------------------------------------
    // 내부 헬퍼
    // -------------------------------------------------------------------------

    private function createReport(Request $request, Post|Comment $target): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'in:' . implode(',', ReportReason::values())],
            'detail' => ['nullable', 'string', 'max:500'],
        ]);

        // 중복 신고 확인
        $exists = $target->reports()
            ->where('reporter_id', $request->user()->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => '이미 신고한 콘텐츠입니다.'], 422);
        }

        Report::create([
            'reporter_id'     => $request->user()->id,
            'reportable_id'   => $target->id,
            'reportable_type' => get_class($target),
            'reason'          => $validated['reason'],
            'detail'          => $validated['detail'] ?? null,
            'status'          => 'pending',
        ]);

        // 신고 수 카운터 증가
        $target->increment('report_count');

        return response()->json(['message' => '신고가 접수되었습니다. 검토 후 처리해 드립니다.'], 201);
    }
}
