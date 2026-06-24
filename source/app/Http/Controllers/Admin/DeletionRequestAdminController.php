<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminActionLog;
use App\Models\DeletionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeletionRequestAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DeletionRequest::with([
                'user:id,nickname',
                'relatedPost:id,title,status',
                'relatedComment:id,content,status',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('request_type', $request->type);
        }

        return Inertia::render('Admin/DeletionRequests/Index', [
            'requests' => $query->paginate(20)->withQueryString()->through(fn ($r) => [
                'id'              => $r->id,
                'request_type'    => $r->request_type,
                'type_label'      => DeletionRequest::typeLabel($r->request_type),
                'requester_name'  => $r->requester_name,
                'requester_email' => $r->requester_email,
                'target_url'      => $r->target_url,
                'blinded_type'    => $r->blinded_type,
                'status'          => $r->status,
                'created_at'      => $r->created_at->format('Y. m. d H:i'),
                'processed_at'    => $r->processed_at?->format('Y. m. d H:i'),
                'related_post'    => $r->relatedPost ? [
                    'id'     => $r->relatedPost->id,
                    'title'  => $r->relatedPost->title,
                    'status' => $r->relatedPost->status->value ?? $r->relatedPost->status,
                ] : null,
            ]),
            'filters'  => $request->only(['status', 'type']),
        ]);
    }

    public function show(DeletionRequest $deletionRequest): Response
    {
        $deletionRequest->load([
            'user:id,nickname,political_type',
            'relatedPost:id,title,content,status,user_id,created_at',
            'relatedComment:id,content,status,user_id,created_at',
        ]);

        return Inertia::render('Admin/DeletionRequests/Show', [
            'request' => [
                'id'              => $deletionRequest->id,
                'request_type'    => $deletionRequest->request_type,
                'type_label'      => DeletionRequest::typeLabel($deletionRequest->request_type),
                'requester_name'  => $deletionRequest->requester_name,
                'requester_email' => $deletionRequest->requester_email,
                'target_url'      => $deletionRequest->target_url,
                'description'     => $deletionRequest->description,
                'blinded_type'    => $deletionRequest->blinded_type,
                'status'          => $deletionRequest->status,
                'created_at'      => $deletionRequest->created_at->format('Y. m. d H:i'),
                'processed_at'    => $deletionRequest->processed_at?->format('Y. m. d H:i'),
                'user'            => $deletionRequest->user,
                'related_post'    => $deletionRequest->relatedPost ? [
                    'id'         => $deletionRequest->relatedPost->id,
                    'title'      => $deletionRequest->relatedPost->title,
                    'content'    => mb_substr(strip_tags($deletionRequest->relatedPost->content ?? ''), 0, 300),
                    'status'     => $deletionRequest->relatedPost->status->value ?? $deletionRequest->relatedPost->status,
                    'created_at' => $deletionRequest->relatedPost->created_at?->format('Y. m. d H:i'),
                ] : null,
            ],
        ]);
    }

    /** 삭제 확정: 블라인드 콘텐츠를 deleted_by_admin 처리 */
    public function confirm(Request $httpRequest, DeletionRequest $deletionRequest): RedirectResponse
    {
        abort_if($deletionRequest->status === 'completed', 422, '이미 처리된 요청입니다.');

        if ($deletionRequest->related_post_id && $deletionRequest->relatedPost) {
            $deletionRequest->relatedPost->update(['status' => PostStatus::DeletedByAdmin]);
            $deletionRequest->relatedPost->user?->decrement('manner_score', 10);
        }

        if ($deletionRequest->related_comment_id && $deletionRequest->relatedComment) {
            $deletionRequest->relatedComment->update(['status' => PostStatus::DeletedByAdmin]);
        }

        $deletionRequest->update([
            'status'       => 'completed',
            'processed_at' => now(),
        ]);

        AdminActionLog::record($httpRequest->user()->id, 'deletion_request_confirm', $deletionRequest);

        return back()->with('success', '콘텐츠가 삭제 처리되었습니다.');
    }

    /** 복구: 블라인드 해제 → 삭제 요청 기각 */
    public function restore(Request $httpRequest, DeletionRequest $deletionRequest): RedirectResponse
    {
        abort_if($deletionRequest->status === 'completed', 422, '이미 삭제 완료된 요청입니다.');

        if ($deletionRequest->related_post_id && $deletionRequest->relatedPost) {
            $deletionRequest->relatedPost->update(['status' => PostStatus::Published]);
        }

        if ($deletionRequest->related_comment_id && $deletionRequest->relatedComment) {
            $deletionRequest->relatedComment->update(['status' => PostStatus::Published]);
        }

        $deletionRequest->update([
            'status'       => 'rejected',
            'processed_at' => now(),
        ]);

        AdminActionLog::record($httpRequest->user()->id, 'deletion_request_restore', $deletionRequest);

        return back()->with('success', '콘텐츠가 복구되었습니다. 삭제 요청이 기각되었습니다.');
    }
}
