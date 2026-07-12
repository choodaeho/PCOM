<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 개인 알림 실시간 브로드캐스트.
 *
 * routes/channels.php의 users.{userId} 채널로 전송.
 * 프론트 NotificationDropdown.vue가 .UserNotification 이벤트를 구독.
 */
class UserNotificationSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Notification $notification,
        public readonly int $unreadCount,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('users.' . $this->notification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'UserNotification';
    }

    /**
     * @return array{notification: array<string, mixed>, unread_count: int}
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id'         => $this->notification->id,
                'type'       => $this->notification->type,
                'title'      => $this->notification->title,
                'message'    => $this->notification->message,
                'url'        => $this->notification->url,
                'read_at'    => $this->notification->read_at,
                'created_at' => $this->notification->created_at?->toIso8601String(),
            ],
            'unread_count' => $this->unreadCount,
        ];
    }
}
