<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * 알림 목록 (최신 30건).
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (Notification $n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'message'    => $n->message,
                'url'        => $n->url,
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => Notification::forUser($request->user()->id)->unread()->count(),
        ]);
    }

    /**
     * 읽지 않은 알림 수만 반환 (폴링용 경량 엔드포인트).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::forUser($request->user()->id)->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * 단일 알림 읽음 처리.
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * 전체 알림 읽음 처리.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * 알림 삭제.
     */
    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->delete();

        return response()->json(['ok' => true]);
    }
}
