<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Profile;
use App\Models\Order;
use App\Models\Commerce;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition()
    {
        return [
            'profile_id' => Profile::factory(),
            'order_id' => Order::factory(),
            'commerce_id' => Commerce::factory(),
            'type' => 'restaurant',
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence,
            'photos' => null,
        ];
    }

    public function forRestaurant()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'restaurant',
                'delivery_agent_id' => null,
            ];
        });
    }

    public function forDeliveryAgent()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'delivery_agent',
                'commerce_id' => null,
            ];
        });
    }
} 