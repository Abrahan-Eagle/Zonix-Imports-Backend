<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        $banks = [
            ['name' => 'Banco de Venezuela', 'code' => '0102', 'type' => 'público'],
            ['name' => 'Banesco', 'code' => '0134', 'type' => 'privado'],
            ['name' => 'Banco Mercantil', 'code' => '0105', 'type' => 'privado'],
            ['name' => 'Banco Provincial', 'code' => '0108', 'type' => 'privado'],
            ['name' => 'Banco del Tesoro', 'code' => '0163', 'type' => 'público'],
            ['name' => 'Banco Bicentenario', 'code' => '0175', 'type' => 'público'],
        ];
        static $usedCodes = [];
        $available = array_filter($banks, fn($b) => !in_array($b['code'], $usedCodes));
        if (empty($available)) {
            $bank = $this->faker->randomElement($banks);
            $code = $this->faker->unique()->numerify('01##');
        } else {
            $bank = $this->faker->randomElement($available);
            $code = $bank['code'];
            $usedCodes[] = $code;
        }
        return [
            'name' => $bank['name'],
            'code' => $code,
            'type' => $bank['type'],
            'swift_code' => null,
            'is_active' => true,
        ];
    }
} 