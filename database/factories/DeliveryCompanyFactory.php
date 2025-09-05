<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryCompany>
 */
class DeliveryCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'name' => $this->faker->company,
            'tax_id' => $this->faker->numerify('J-########-#'),
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'activo' => $this->faker->boolean(80), // 80% probability of being active
        ];
    }
}
