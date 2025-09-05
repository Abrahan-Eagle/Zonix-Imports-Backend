<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\Commerce;
use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        $types = ['card', 'mobile_payment', 'cash', 'paypal', 'digital_wallet', 'bank_transfer'];
        $type = $this->faker->randomElement($types);
        $bank = Bank::inRandomOrder()->first();
        $data = [
            'payable_type' => 'App\\Models\\User',
            'payable_id' => \App\Models\User::factory(),
            'type' => $type,
            'is_default' => false,
            'is_active' => true,
            'bank_id' => in_array($type, ['mobile_payment', 'bank_transfer']) ? ($bank ? $bank->id : null) : null,
        ];
        if ($type === 'card') {
            $data = array_merge($data, [
                'brand' => $this->faker->randomElement(['Visa', 'Mastercard', 'Amex']),
                'last4' => $this->faker->numerify('####'),
                'exp_month' => $this->faker->numberBetween(1, 12),
                'exp_year' => $this->faker->numberBetween(date('Y'), date('Y') + 5),
                'cardholder_name' => $this->faker->name,
            ]);
        } elseif ($type === 'mobile_payment') {
            $data = array_merge($data, [
                'phone' => $this->faker->numerify('04#########'),
                'reference_info' => json_encode([
                    'cedula' => $this->faker->numerify('########'),
                    'reference_number' => $this->faker->numerify('##########'),
                ]),
            ]);
        } elseif ($type === 'bank_transfer') {
            $data = array_merge($data, [
                'account_number' => $this->faker->numerify('##########'),
            ]);
        } elseif ($type === 'paypal' || $type === 'digital_wallet') {
            $data = array_merge($data, [
                'email' => $this->faker->safeEmail,
            ]);
        }
        $data['owner_name'] = $this->faker->name;
        $data['owner_id'] = $this->faker->numerify('########');
        return $data;
    }
} 