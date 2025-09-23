<?php

namespace Tests\Feature\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

class MvpRolesGuestBuyerSellerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_buyer_seller_end_to_end_smoke()
    {
        // 1) Guest: puede acceder a endpoint público y NO a rutas buyer
        $this->getJson('/api/ping')->assertOk();
        $this->getJson('/api/buyer/cart')->assertStatus(401);

        // 2) Seller: publica producto (vía modelos por rol legacy en rutas)
        $seller = Profile::factory()->seller()->create(['is_verified' => true]);
        $commerce = Commerce::factory()->create(['profile_id' => $seller->id, 'is_verified' => true, 'open' => true]);
        $publishedProduct = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 20.00,
            'stock' => 30,
            'modality' => 'retail',
            'available' => true,
        ]);

        // 3) Buyer: compra vía endpoints
        $buyerUser = User::factory()->create(['role' => 'users']);
        Sanctum::actingAs($buyerUser);

        // Agregar al carrito
        $this->postJson('/api/buyer/cart/add', [
            'product_id' => $publishedProduct->id,
            'quantity' => 2,
        ])->assertStatus(200);

        // Crear orden (payload mínimo requerido)
        $orderTotal = 2 * $publishedProduct->base_price;
        $orderResponse = $this->postJson('/api/buyer/orders', [
            'commerce_id' => $commerce->id,
            'delivery_type' => 'pickup',
            'products' => [
                [
                    'product_id' => $publishedProduct->id,
                    'quantity' => 2,
                    'unit_price' => $publishedProduct->base_price,
                ],
            ],
            'total' => $orderTotal,
        ]);
        $orderResponse->assertStatus(201);

        // Validación mínima por DB
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $publishedProduct->id,
            'quantity' => 2,
        ]);

        // Nota: el flujo de pago y estados ya está cubierto en otros E2E; aquí smoke de roles
    }
}


