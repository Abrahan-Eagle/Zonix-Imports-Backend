<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Commerce;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea 5 productos para cada comercio existente
        $commerces = Commerce::all();
        foreach ($commerces as $commerce) {
            Product::factory()->count(5)->create(['commerce_id' => $commerce->id]);
        }
    }
}
