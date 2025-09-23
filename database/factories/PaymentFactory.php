<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $method = $this->faker->randomElement(['stripe', 'paypal', 'binance', 'pago_movil', 'zelle']);
        // Estados válidos según migración: pending, succeeded, failed, refunded, cancelled
        $status = $this->faker->randomElement(['pending', 'succeeded', 'failed', 'refunded', 'cancelled']);
        
        return [
            'order_id' => Order::factory(),
            'method' => $method,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'status' => $status,
            'reference' => $this->faker->optional(0.8)->regexify('[A-Z0-9]{10}'),
            'external_id' => $this->faker->optional(0.7)->uuid(),
            'currency' => 'USD',
            'processed_at' => $status === 'succeeded' ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'receipt_url' => $this->faker->optional(0.6)->url(),
            'failure_reason' => $status === 'failed' ? $this->faker->sentence() : null,
            'metadata' => $this->faker->optional(0.5)->randomElements([
                'transaction_id' => $this->faker->uuid(),
                'gateway_response' => $this->faker->sentence(),
                'customer_id' => $this->faker->uuid(),
            ], 3),
        ];
    }
}
