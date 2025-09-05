<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $orderId;
    public $senderId;
    public $senderName;
    public $senderRole;

    /**
     * Create a new event instance.
     */
    public function __construct($message, $orderId, $senderId, $senderName, $senderRole)
    {
        $this->message = $message;
        $this->orderId = $orderId;
        $this->senderId = $senderId;
        $this->senderName = $senderName;
        $this->senderRole = $senderRole;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->orderId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'order_id' => $this->orderId,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'sender_role' => $this->senderRole,
            'timestamp' => now()->toISOString(),
        ];
    }
} 