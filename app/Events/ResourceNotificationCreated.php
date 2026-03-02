<?php

namespace App\Events;

use App\Models\ResourceNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResourceNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public ResourceNotification $notification)
    {
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("notifications.{$this->notification->recipient_id}");
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'message' => $this->notification->message,
            'redirect_url' => $this->notification->redirect_url,
            'action' => $this->notification->action,
        ];
    }

    public function broadcastAs(): string
    {
        return 'ResourceNotificationCreated';
    }
}
