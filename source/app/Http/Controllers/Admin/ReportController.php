<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Report::with(['reporter:id,nickname', 'reportable'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'reason']),
        ]);
    }

    public function show(Report $report): Response
    {
        $report->load(['reporter:id,nickname', 'reviewer:id,nickname', 'reportable']);

        return Inertia::render('Admin/Reports/Show', ['report' => $report]);
    }

    public function action(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        $report->update([
            'status'      => 'actioned',
            'admin_note'  => $validated['admin_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($report->reportable) {
            $report->reportable->update(['status' => PostStatus::Hidden]);

            if ($report->reportable->user) {
                $report->reportable->user->decrement('manner_score', 10);
            }
        }

        AdminActionLog::record($request->user()->id, 'report_action', $report);

        return back()->with('success', '신고를 처리했습니다.');
    }

    public function dismiss(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status'      => 'dismissed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', '신고를 기각했습니다.');
    }

    /** 신고로 숨겨진 콘텐츠를 복구 */
    public function restoreContent(Request $request, Report $report): RedirectResponse
    {
        if ($report->reportable && $report->reportable->status === PostStatus::Hidden) {
            $report->reportable->update(['status' => PostStatus::Published]);
        }

        $report->update([
            'status'      => 'dismissed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AdminActionLog::record($request->user()->id, 'report_restore_content', $report);

        return back()->with('success', '콘텐츠가 복구되었습니다.');
    }
}
