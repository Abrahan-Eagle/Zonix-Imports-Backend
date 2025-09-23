<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use Laravel\Sanctum\Sanctum;

class PaymentManualTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsBuyer()
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'buyer@test.com',
        ]);
        Profile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);
        return $this;
    }

    private function setupSellerProduct(): array
    {
        $seller = User::factory()->create(['email' => 'seller@test.com']);
        $sellerProfile = Profile::factory()->create(['user_id' => $seller->id, 'is_verified' => true]);
        $commerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id, 'is_verified' => true, 'open' => true]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'category_id' => $category->id,
            'base_price' => 10,
            'stock' => 100,
            'available' => true,
        ]);
        return [$commerce, $product];
    }

    /** @test */
    public function pago_manual_zelle_y_pago_movil()
    {
        [$commerce, $product] = $this->setupSellerProduct();
        $this->actingAsBuyer();

        // Agregar al carrito (MVP: checkout usa el carrito)
        $add = $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $add->assertStatus(200);

        // Checkout (MVP: sin necesidad de pasar productos en body)
        $resp = $this->postJson('/api/checkout', []);
        $resp->assertStatus(201);
        $orderId = $resp->json('data.id') ?? $resp->json('order.id') ?? $resp->json('id');
        if (empty($orderId)) {
            $orderId = Order::latest('id')->value('id');
        }
        $this->assertNotEmpty($orderId);

        // Zelle
        $zelle = $this->postJson('/api/payments/comprobante', [
            'order_id' => $orderId,
            'method' => 'zelle',
            'reference' => 'Z-' . uniqid(),
            'amount' => 20,
            'receipt_url' => 'https://example.com/receipt-zelle.jpg',
        ]);
        $zelle->assertStatus(201)->assertJsonStructure(['payment']);

        // Pago Móvil
        $pm = $this->postJson('/api/payments/comprobante', [
            'order_id' => $orderId,
            'method' => 'pago_movil',
            'reference' => 'PM-' . uniqid(),
            'amount' => 20,
            'receipt_url' => 'https://example.com/receipt-pm.jpg',
        ]);
        $pm->assertStatus(201)->assertJsonStructure(['payment']);

        // Estado de orden sigue accesible
        $order = Order::find($orderId);
        $this->assertEquals($commerce->id, $order->commerce_id);
    }
}


