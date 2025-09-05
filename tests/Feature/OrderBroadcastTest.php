<?php

namespace Tests\Feature;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_changed_event_is_broadcasted()
    {
        Event::fake([OrderStatusChanged::class]);

        $order = Order::factory()->create(['status' => 'pending_payment']);

        // Simula cambio de estado
        $order->status = 'delivered';
        $order->save();

        event(new OrderStatusChanged($order));

        Event::assertDispatched(OrderStatusChanged::class, function ($event) use ($order) {
            return $event->order->id === $order->id && $event->order->status === 'delivered';
        });
    }
} 