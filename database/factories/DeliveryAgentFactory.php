<?php

namespace Database\Factories;

use App\Models\DeliveryCompany;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryAgent>
 */
class DeliveryAgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => DeliveryCompany::factory(),
            'profile_id' => Profile::factory(),
            'status' => $this->faker->randomElement(['activo', 'inactivo', 'suspendido']),
            'working' => $this->faker->boolean,
            'rating' => $this->faker->randomFloat(2, 1, 5),
            'vehicle_type' => $this->faker->randomElement(['motorcycle', 'car', 'bicycle', 'truck']),
            'phone' => $this->faker->phoneNumber,
        ];
    }
}
