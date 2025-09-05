<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentValidated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $isValidated;
    public $validatedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, bool $isValidated, $validatedBy = null)
    {
        $this->order = $order;
        $this->isValidated = $isValidated;
        $this->validatedBy = $validatedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.' . $this->order->id),
            new PrivateChannel('user.' . $this->order->user_id),
            new PrivateChannel('commerce.' . $this->order->commerce_id),
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
            'order_id' => $this->order->id,
            'order_number' => $this->order->orderNumber ?? 'ORD-' . $this->order->id,
            'is_validated' => $this->isValidated,
            'validated_by' => $this->validatedBy,
            'status' => $this->order->status,
            'message' => $this->isValidated ? 'Pago validado correctamente' : 'Pago rechazado',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'PaymentValidated';
    }
} 