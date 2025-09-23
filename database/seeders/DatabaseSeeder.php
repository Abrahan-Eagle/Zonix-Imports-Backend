<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\CartItem;
use App\Models\InventoryMovement;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders de datos base primero
        $this->call([
            CategorySeeder::class,
            BanksSeeder::class,
            OperatorCodeSeeder::class,
            CountriesSeeder::class,
            StatesSeeder::class,
            CitiesSeeder::class,
        ]);

        // Crear administrador
        $adminProfile = Profile::factory()->admin()->create([
            'firstName' => 'Admin',
            'lastName' => 'System',
            'is_verified' => true,
        ]);

        // Crear vendedores con comercios
        $sellers = Profile::factory()->seller()->count(5)->create();
        $commerces = collect();

        $sellers->each(function ($profile) use (&$commerces) {
            $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
            $commerces->push($commerce);

            // Crear productos para cada comercio
            $products = Product::factory()->count(8)->create(['commerce_id' => $commerce->id]);
            
            // Crear imágenes para algunos productos
            $products->take(3)->each(function ($product) {
                ProductImage::factory()->count(2)->create(['product_id' => $product->id]);
            });

            // Crear referidos para algunos productos
            $products->take(2)->each(function ($product) use ($profile) {
                Referral::factory()->create([
                    'product_id' => $product->id,
                    'referrer_profile_id' => $profile->id,
                ]);
            });

            // Crear movimientos de inventario
            $products->each(function ($product) use ($profile) {
                InventoryMovement::factory()->count(2)->create([
                    'product_id' => $product->id,
                    'user_id' => $profile->user_id,
                ]);
            });
        });

        // Crear compradores
        $buyers = Profile::factory()->buyer()->count(15)->create();

        // Crear carritos para algunos compradores
        $buyers->take(8)->each(function ($profile) use ($commerces) {
            $products = Product::whereIn('commerce_id', $commerces->pluck('id'))
                             ->inRandomOrder()
                             ->take(3)
                             ->get();

            $products->each(function ($product) use ($profile) {
                CartItem::factory()->create([
                    'profile_id' => $profile->id,
                    'product_id' => $product->id,
                ]);
            });
        });

        // Crear órdenes
        $buyers->each(function ($profile) use ($commerces) {
            $commerce = $commerces->random();
            $order = Order::factory()->create([
                'profile_id' => $profile->id,
                'commerce_id' => $commerce->id,
            ]);

            // Crear items de la orden
            $products = $commerce->products()->inRandomOrder()->take(2)->get();
            $products->each(function ($product) use ($order) {
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ]);
            });

            // Crear pagos para algunas órdenes
            if (fake()->boolean(70)) {
                Payment::factory()->create(['order_id' => $order->id]);
            }
        });

        // Crear notificaciones
        $allProfiles = $buyers->concat($sellers)->concat([$adminProfile]);
        $allProfiles->each(function ($profile) {
            Notification::factory()->count(3)->create(['profile_id' => $profile->id]);
        });
    }
}
