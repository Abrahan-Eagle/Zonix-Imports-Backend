<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = \App\Models\Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'firstName' => $this->faker->firstName,
            'middleName' => $this->faker->firstName,
            'lastName' => $this->faker->lastName,
            'secondLastName' => $this->faker->lastName,
            'photo_users' => $this->faker->imageUrl(),
            'date_of_birth' => $this->faker->date(),
            'maritalStatus' => $this->faker->randomElement(['married', 'divorced', 'single', 'widowed']),
            'sex' => $this->faker->randomElement(['F', 'M', 'O']),
            'status' => $this->faker->randomElement(['completeData', 'incompleteData', 'notverified']),
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
        ];
    }

    // Estados adicionales para diferentes roles
    public function commerce(): Factory
    {
        return $this->state([
        ]);
    }

    public function delivery(): Factory
    {
        return $this->state([
        ]);
    }
}
