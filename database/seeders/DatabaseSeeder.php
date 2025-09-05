<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Commerce;
use App\Models\DeliveryAgent;
use App\Models\DeliveryCompany;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuarios base con sus perfiles
        $profiles = Profile::factory()->count(10)->create();

        // Crear comercios para algunos perfiles (roles de comercio)
        $profiles->take(3)->each(function ($profile) {
            $profile->user->update(['role' => 'commerce']);
            $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);

            // Crear productos y publicaciones para cada comercio
            Product::factory()->count(5)->create(['commerce_id' => $commerce->id]);
            Post::factory()->count(3)->create(['commerce_id' => $commerce->id]);
        });

        // Marcar algunos perfiles como compradores
        $buyers = $profiles->whereNotIn('id', [1, 2, 3]);
        $buyers->each(function ($profile) {
            $profile->user->update(['role' => 'users']);
        });

        // Crear publicaciones para likear
        $posts = Post::all();
        $profiles->each(function ($profile) use ($posts) {
            $liked = $posts->random(2);
            foreach ($liked as $post) {
                PostLike::factory()->create([
                    'profile_id' => $profile->id,
                    'post_id' => $post->id,
                ]);
            }
        });

        // Crear órdenes para usuarios compradores
        $commerces = Commerce::all();
        $buyers->each(function ($profile) use ($commerces) {
            $commerce = $commerces->random();
            $order = Order::factory()->create([
                'profile_id' => $profile->id,
                'commerce_id' => $commerce->id,
            ]);

            $products = $commerce->products()->inRandomOrder()->take(2)->get();
            foreach ($products as $product) {
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ]);
            }
        });

        // Crear empresas y agentes de delivery
        $deliveryCompanies = [];
        Profile::factory()->count(2)->create()->each(function ($profile) use (&$deliveryCompanies) {
            $profile->user->update(['role' => 'delivery_company']);
            $deliveryCompanies[] = DeliveryCompany::factory()->create([
                'profile_id' => $profile->id
            ]);
        });

        foreach ($deliveryCompanies as $company) {
            Profile::factory()->count(2)->create()->each(function ($profile) use ($company) {
                $profile->user->update(['role' => 'delivery_agent']);
                $agent = DeliveryAgent::factory()->create([
                    'company_id' => $company->id,
                    'profile_id' => $profile->id
                ]);

                // Asignar agente a un pedido existente
                $order = Order::inRandomOrder()->first();
                if ($order) {
                    OrderDelivery::factory()->create([
                        'order_id' => $order->id,
                        'agent_id' => $agent->id,
                    ]);
                }
            });
        }

        $this->call([
            CategorySeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            CommerceSeeder::class,
            ProductSeeder::class,
            PostSeeder::class,
            PostLikeSeeder::class,
            OrderSeeder::class,
            OrderItemSeeder::class,
            DeliveryCompanySeeder::class,
            DeliveryAgentSeeder::class,
            OrderDeliverySeeder::class,
            // ReviewSeeder::class, // Comentado temporalmente por problemas de migración
            NotificationSeeder::class,
            BanksSeeder::class, // Agregado para precargar bancos
            PaymentMethodSeeder::class, // Agregado para poblar métodos de pago


            OperatorCodeSeeder::class,
            CountriesSeeder::class,
            StatesSeeder::class,
            CitiesSeeder::class,
        ]);
    }
}
