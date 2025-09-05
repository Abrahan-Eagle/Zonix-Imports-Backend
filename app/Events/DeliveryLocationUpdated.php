<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderId;
    public $deliveryAgentId;
    public $latitude;
    public $longitude;
    public $estimatedArrival;

    /**
     * Create a new event instance.
     */
    public function __construct($orderId, $deliveryAgentId, $latitude, $longitude, $estimatedArrival = null)
    {
        $this->orderId = $orderId;
        $this->deliveryAgentId = $deliveryAgentId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->estimatedArrival = $estimatedArrival;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.' . $this->orderId),
            new PrivateChannel('delivery.' . $this->deliveryAgentId),
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
            'order_id' => $this->orderId,
            'delivery_agent_id' => $this->deliveryAgentId,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'estimated_arrival' => $this->estimatedArrival,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'DeliveryLocationUpdated';
    }
} 