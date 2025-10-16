<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Product;
use App\Models\Commerce;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function flujo_completo_de_carrito()
    {
        // 1. Crear usuario comprador
        $user = User::factory()->create();
        $profile = Profile::factory()->buyer()->create(['user_id' => $user->id]);

        // 2. Crear productos de prueba
        $commerce = Commerce::factory()->create();
        $category = Category::factory()->create();
        
        $product1 = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'category_id' => $category->id,
            'name' => 'Producto Test 1',
            'base_price' => 25.00,
            'stock' => 20,
            'available' => true
        ]);

        $product2 = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'category_id' => $category->id,
            'name' => 'Producto Test 2',
            'base_price' => 35.00,
            'stock' => 15,
            'available' => true
        ]);

        // 3. Agregar primer producto al carrito
        $response1 = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', [
                'product_id' => $product1->id,
                'quantity' => 2,
                'modality' => 'retail'
            ]);

        $response1->assertStatus(201)
            ->assertJsonPath('data.items_count', 1)
            ->assertJsonPath('data.cart_total', function($total) {
                return $total >= 50.00; // 2 * 25 + envío
            });

        $cartItem1Id = $response1->json('data.cart_item.id');

        // 4. Agregar segundo producto
        $response2 = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', [
                'product_id' => $product2->id,
                'quantity' => 1,
                'modality' => 'retail'
            ]);

        $response2->assertStatus(201)
            ->assertJsonPath('data.items_count', 2);

        $cartItem2Id = $response2->json('data.cart_item.id');

        // 5. Ver carrito completo
        $response3 = $this->actingAs($user)
            ->getJson('/api/buyer/cart');

        $response3->assertStatus(200)
            ->assertJsonPath('data.summary.items_count', 2);

        $this->assertCount(2, $response3->json('data.items'));
        $this->assertEquals(85.00, (float)$response3->json('data.summary.subtotal'));

        // 6. Actualizar cantidad del primer producto
        $response4 = $this->actingAs($user)
            ->putJson("/api/cart/{$cartItem1Id}", [
                'quantity' => 5
            ]);

        $response4->assertStatus(200)
            ->assertJsonPath('data.cart_item.quantity', 5);
        
        $this->assertEquals(125.00, (float)$response4->json('data.cart_item.subtotal'));

        // 7. Validar stock del carrito
        $response5 = $this->actingAs($user)
            ->getJson('/api/buyer/cart/validate');

        $response5->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.errors', []);

        // 8. Hacer un producto no disponible y validar
        $product1->update(['available' => false]);

        $response6 = $this->actingAs($user)
            ->getJson('/api/buyer/cart/validate');

        $response6->assertStatus(400)
            ->assertJsonPath('data.valid', false);

        $this->assertNotEmpty($response6->json('data.errors'));

        // 9. Restaurar disponibilidad y eliminar un item
        $product1->update(['available' => true]);

        $response7 = $this->actingAs($user)
            ->deleteJson("/api/cart/{$cartItem2Id}");

        $response7->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem2Id]);

        // 10. Verificar que solo queda 1 item
        $response8 = $this->actingAs($user)
            ->getJson('/api/buyer/cart');

        $response8->assertStatus(200)
            ->assertJsonPath('data.summary.items_count', 1);

        // 11. Limpiar carrito completamente
        $response9 = $this->actingAs($user)
            ->deleteJson('/api/buyer/cart');

        $response9->assertStatus(200)
            ->assertJsonPath('data.items_deleted', 1);

        // 12. Verificar carrito vacío
        $response10 = $this->actingAs($user)
            ->getJson('/api/buyer/cart');

        $response10->assertStatus(200)
            ->assertJsonPath('data.summary.items_count', 0)
            ->assertJsonPath('data.summary.total', 0);

        $this->assertCount(0, $response10->json('data.items'));
    }

    /** @test */
    public function flujo_mayorista_con_validacion_cantidad_minima()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->buyer()->create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'base_price' => 30.00,
            'wholesale_price' => 25.00,
            'min_wholesale_quantity' => 10,
            'stock' => 100,
            'available' => true
        ]);

        // Intentar agregar con cantidad menor a la mínima
        $response1 = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', [
                'product_id' => $product->id,
                'quantity' => 5,
                'modality' => 'wholesale'
            ]);

        $response1->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', function($message) {
                return str_contains($message, 'Cantidad mínima');
            });

        // Agregar con cantidad válida
        $response2 = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', [
                'product_id' => $product->id,
                'quantity' => 15,
                'modality' => 'wholesale'
            ]);

        $response2->assertStatus(201);
        
        $this->assertEquals(25.00, (float)$response2->json('data.cart_item.unit_price'));
        $this->assertEquals(375.00, (float)$response2->json('data.cart_item.subtotal'));
    }

    /** @test */
    public function flujo_con_multiples_comercios_calcula_envio_correcto()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->buyer()->create(['user_id' => $user->id]);

        $commerce1 = Commerce::factory()->create();
        $commerce2 = Commerce::factory()->create();

        $product1 = Product::factory()->create([
            'commerce_id' => $commerce1->id,
            'base_price' => 20.00,
            'stock' => 10
        ]);

        $product2 = Product::factory()->create([
            'commerce_id' => $commerce2->id,
            'base_price' => 15.00,
            'stock' => 10
        ]);

        // Agregar productos de diferentes tiendas
        $this->actingAs($user)->postJson('/api/buyer/cart/add', [
            'product_id' => $product1->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);

        $this->actingAs($user)->postJson('/api/buyer/cart/add', [
            'product_id' => $product2->id,
            'quantity' => 1,
            'modality' => 'retail'
        ]);

        // Ver carrito
        $response = $this->actingAs($user)->getJson('/api/buyer/cart');

        $response->assertStatus(200);
        $this->assertEquals(35.00, (float)$response->json('data.summary.subtotal'));
        $this->assertEquals(20.00, (float)$response->json('data.summary.shipping'));
    }
}

