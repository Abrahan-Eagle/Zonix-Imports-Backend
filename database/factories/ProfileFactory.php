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
            'lastName' => $this->faker->lastName,
            'role' => $this->faker->randomElement(['buyer', 'seller', 'admin']),
            'is_verified' => $this->faker->boolean(60),
            'rif' => $this->faker->optional(0.3)->regexify('V[0-9]{8}'),
            'bank_account' => $this->faker->optional(0.2)->bankAccountNumber,
        ];
    }

    /**
     * Estado para comprador
     */
    public function buyer(): Factory
    {
        return $this->state([
            'role' => 'buyer',
            'is_verified' => true,
        ]);
    }

    /**
     * Estado para vendedor
     */
    public function seller(): Factory
    {
        return $this->state([
            'role' => 'seller',
            'is_verified' => $this->faker->boolean(80),
            'rif' => $this->faker->regexify('V[0-9]{8}'),
            'bank_account' => $this->faker->bankAccountNumber,
        ]);
    }

    /**
     * Estado para administrador
     */
    public function admin(): Factory
    {
        return $this->state([
            'role' => 'admin',
            'is_verified' => true,
        ]);
    }
}
