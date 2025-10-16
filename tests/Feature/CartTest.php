<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Commerce;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_agregar_producto_al_carrito()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'stock' => 10,
            'base_price' => 25.00,
            'available' => true
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', [
                'product_id' => $product->id,
                'quantity' => 2,
                'modality' => 'retail'
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Producto agregado al carrito exitosamente'
            ])
            ->assertJsonStructure([
                'data' => [
                    'cart_item',
                    'cart_total',
                    'items_count'
                ]
            ]);

        $this->assertDatabaseHas('cart_items', [
            'profile_id' => $profile->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    /** @test */
    public function requiere_autenticacion_para_agregar()
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/buyer/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function valida_campos_requeridos()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity', 'modality']);
    }

    /** @test */
    public function rechaza_cantidad_negativa()
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', [
                'product_id' => $product->id,
                'quantity' => -1,
                'modality' => 'retail'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /** @test */
    public function rechaza_modalidad_invalida()
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/buyer/cart/add', [
                'product_id' => $product->id,
                'quantity' => 1,
                'modality' => 'invalid_modality'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['modality']);
    }

    /** @test */
    public function puede_obtener_carrito()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 10, 'base_price' => 15.00]);

        CartItem::create([
            'profile_id' => $profile->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'modality' => 'retail',
            'unit_price' => 15.00,
            'subtotal' => 45.00
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/buyer/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'data' => [
                    'items',
                    'summary' => [
                        'items_count',
                        'subtotal',
                        'shipping',
                        'discount',
                        'total'
                    ]
                ]
            ]);

        $this->assertEquals(1, $response->json('data.summary.items_count'));
        $this->assertEquals(45.00, $response->json('data.summary.subtotal'));
    }

    /** @test */
    public function puede_actualizar_cantidad()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 20, 'base_price' => 10.00]);

        $cartItem = CartItem::create([
            'profile_id' => $profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 10.00,
            'subtotal' => 20.00
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/cart/{$cartItem->id}", [
                'quantity' => 5
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cantidad actualizada exitosamente'
            ]);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
            'subtotal' => 50.00
        ]);
    }

    /** @test */
    public function puede_eliminar_item()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 10]);

        $cartItem = CartItem::create([
            'profile_id' => $profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 10.00,
            'subtotal' => 20.00
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/cart/{$cartItem->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Producto eliminado del carrito'
            ]);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    /** @test */
    public function puede_limpiar_carrito_completo()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $product1 = Product::factory()->create(['stock' => 10]);
        $product2 = Product::factory()->create(['stock' => 10]);

        CartItem::create([
            'profile_id' => $profile->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 10.00,
            'subtotal' => 20.00
        ]);

        CartItem::create([
            'profile_id' => $profile->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'modality' => 'retail',
            'unit_price' => 15.00,
            'subtotal' => 15.00
        ]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/buyer/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonPath('data.items_deleted', 2);

        $this->assertEquals(0, CartItem::where('profile_id', $profile->id)->count());
    }

    /** @test */
    public function puede_validar_stock_del_carrito()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 5, 'available' => true]);

        CartItem::create([
            'profile_id' => $profile->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'modality' => 'retail',
            'unit_price' => 10.00,
            'subtotal' => 30.00
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/buyer/cart/validate');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Carrito válido',
                'data' => [
                    'valid' => true,
                    'errors' => []
                ]
            ]);
    }

    /** @test */
    public function detecta_stock_insuficiente_en_validacion()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 2, 'available' => true]);

        CartItem::create([
            'profile_id' => $profile->id,
            'product_id' => $product->id,
            'quantity' => 5, // Más de lo disponible
            'modality' => 'retail',
            'unit_price' => 10.00,
            'subtotal' => 50.00
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/buyer/cart/validate');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [
                    'valid' => false
                ]
            ]);

        $this->assertNotEmpty($response->json('data.errors'));
    }

    /** @test */
    public function actualizar_item_de_otro_usuario_falla()
    {
        $user1 = User::factory()->create();
        $profile1 = Profile::factory()->create(['user_id' => $user1->id]);
        $user2 = User::factory()->create();
        Profile::factory()->create(['user_id' => $user2->id]);
        
        $product = Product::factory()->create(['stock' => 20]);

        $cartItem = CartItem::create([
            'profile_id' => $profile1->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 10.00,
            'subtotal' => 20.00
        ]);

        $response = $this->actingAs($user2)
            ->putJson("/api/cart/{$cartItem->id}", [
                'quantity' => 5
            ]);

        // Debe fallar con 400 porque el item no pertenece al usuario
        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }
}

