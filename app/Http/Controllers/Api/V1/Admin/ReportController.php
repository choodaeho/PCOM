<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    /**
     * GET /api/v1/admin/reports
     *
     * 신고 목록 (필터: status, reason).
     */
    public function index(Request $request): JsonResponse
    {
        $reports = Report::with(['reporter:id,nickname', 'reviewer:id,nickname', 'reportable'])
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('reason'), fn ($q, $r) => $q->where('reason', $r))
            ->latest()
            ->paginate(20);

        return response()->json($reports);
    }

    /**
     * GET /api/v1/admin/reports/{report}
     */
    public function show(Report $report): JsonResponse
    {
        return response()->json($report->load(['reporter', 'reviewer', 'reportable']));
    }

    /**
     * POST /api/v1/admin/reports/{report}/action
     *
     * 신고 처리: 콘텐츠 숨김 + 사용자 manner_score 감점.
     * Body: { "admin_note": "..." }
     */
    public function action(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $target = $report->reportable;

        if ($target !== null) {
            // 콘텐츠 숨김
            $target->update(['status' => 'deleted_by_admin']);

            // 작성자 manner_score 감점 (-10점)
            $target->user?->decrement('manner_score', 10);
        }

        $report->update([
            'status'      => 'actioned',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note'  => $validated['admin_note'] ?? null,
        ]);

        AdminActionLog::record(
            $request->user()->id,
            'report.action',
            $report,
            ['admin_note' => $validated['admin_note']]
        );

        return response()->json(['message' => '신고 처리가 완료되었습니다.']);
    }

    /**
     * POST /api/v1/admin/reports/{report}/dismiss
     *
     * 신고 기각.
     */
    public function dismiss(Request $request, Report $report): JsonResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $report->update([
            'status'      => 'dismissed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note'  => $validated['admin_note'] ?? null,
        ]);

        AdminActionLog::record($request->user()->id, 'report.dismiss', $report);

        return response()->json(['message' => '신고가 기각되었습니다.']);
    }
}
