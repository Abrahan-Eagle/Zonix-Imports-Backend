<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CartService;
use App\Models\Profile;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\Commerce;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }

    /** @test */
    public function puede_agregar_producto_al_carrito()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create([
            'stock' => 10,
            'base_price' => 25.00,
            'available' => true
        ]);

        $cartItem = $this->cartService->addItem($profile, $product->id, 2, 'retail');

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertEquals($profile->id, $cartItem->profile_id);
        $this->assertEquals($product->id, $cartItem->product_id);
        $this->assertEquals(2, $cartItem->quantity);
        $this->assertEquals(25.00, $cartItem->unit_price);
        $this->assertEquals(50.00, $cartItem->subtotal);
        
        $this->assertDatabaseHas('cart_items', [
            'profile_id' => $profile->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    /** @test */
    public function lanza_excepcion_si_producto_no_existe()
    {
        $profile = Profile::factory()->buyer()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Producto no encontrado');

        $this->cartService->addItem($profile, 99999, 1, 'retail');
    }

    /** @test */
    public function lanza_excepcion_si_producto_no_disponible()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create(['available' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Producto no disponible');

        $this->cartService->addItem($profile, $product->id, 1, 'retail');
    }

    /** @test */
    public function lanza_excepcion_si_stock_insuficiente()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create([
            'stock' => 3,
            'available' => true
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->cartService->addItem($profile, $product->id, 5, 'retail');
    }

    /** @test */
    public function valida_cantidad_minima_para_mayorista()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create([
            'stock' => 100,
            'min_wholesale_quantity' => 10,
            'wholesale_price' => 20.00,
            'available' => true
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cantidad mínima para mayorista: 10 unidades');

        $this->cartService->addItem($profile, $product->id, 5, 'wholesale');
    }

    /** @test */
    public function puede_agregar_producto_mayorista_con_cantidad_valida()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create([
            'stock' => 100,
            'base_price' => 25.00,
            'min_wholesale_quantity' => 10,
            'wholesale_price' => 20.00,
            'available' => true
        ]);

        $cartItem = $this->cartService->addItem($profile, $product->id, 15, 'wholesale');

        $this->assertEquals(20.00, $cartItem->unit_price); // Precio mayorista
        $this->assertEquals(300.00, $cartItem->subtotal); // 15 * 20
    }

    /** @test */
    public function puede_obtener_carrito_vacio()
    {
        $profile = Profile::factory()->buyer()->create();

        $cart = $this->cartService->getCart($profile);

        $this->assertIsArray($cart);
        $this->assertArrayHasKey('items', $cart);
        $this->assertArrayHasKey('summary', $cart);
        $this->assertCount(0, $cart['items']);
        $this->assertEquals(0, $cart['summary']['items_count']);
        $this->assertEquals(0, $cart['summary']['total']);
    }

    /** @test */
    public function puede_obtener_carrito_con_items()
    {
        $profile = Profile::factory()->buyer()->create();
        $product1 = Product::factory()->create(['base_price' => 10.00, 'stock' => 10, 'available' => true]);
        $product2 = Product::factory()->create(['base_price' => 20.00, 'stock' => 10, 'available' => true]);

        $this->cartService->addItem($profile, $product1->id, 2, 'retail');
        $this->cartService->addItem($profile, $product2->id, 1, 'retail');

        $cart = $this->cartService->getCart($profile);

        $this->assertCount(2, $cart['items']);
        $this->assertEquals(2, $cart['summary']['items_count']);
        $this->assertEquals(40.00, $cart['summary']['subtotal']); // (10*2) + (20*1)
    }

    /** @test */
    public function puede_actualizar_cantidad()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create(['stock' => 10, 'base_price' => 15.00, 'available' => true]);
        
        $cartItem = $this->cartService->addItem($profile, $product->id, 2, 'retail');

        $updated = $this->cartService->updateQuantity($profile, $cartItem->id, 5);

        $this->assertEquals(5, $updated->quantity);
        $this->assertEquals(75.00, $updated->subtotal); // 5 * 15
    }

    /** @test */
    public function lanza_excepcion_al_actualizar_con_stock_insuficiente()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create(['stock' => 3, 'available' => true]);
        
        $cartItem = $this->cartService->addItem($profile, $product->id, 2, 'retail');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->cartService->updateQuantity($profile, $cartItem->id, 10);
    }

    /** @test */
    public function puede_eliminar_item()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create(['stock' => 10, 'available' => true]);
        
        $cartItem = $this->cartService->addItem($profile, $product->id, 2, 'retail');

        $result = $this->cartService->removeItem($profile, $cartItem->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    /** @test */
    public function puede_limpiar_carrito_completo()
    {
        $profile = Profile::factory()->buyer()->create();
        $product1 = Product::factory()->create(['stock' => 10, 'available' => true]);
        $product2 = Product::factory()->create(['stock' => 10, 'available' => true]);
        
        $this->cartService->addItem($profile, $product1->id, 2, 'retail');
        $this->cartService->addItem($profile, $product2->id, 1, 'retail');

        $count = $this->cartService->clearCart($profile);

        $this->assertEquals(2, $count);
        $this->assertEquals(0, CartItem::where('profile_id', $profile->id)->count());
    }

    /** @test */
    public function calcula_envio_correctamente()
    {
        $profile = Profile::factory()->buyer()->create();
        $commerce1 = Commerce::factory()->create();
        $commerce2 = Commerce::factory()->create();
        
        $product1 = Product::factory()->create([
            'commerce_id' => $commerce1->id,
            'base_price' => 10.00,
            'stock' => 10,
            'available' => true
        ]);
        $product2 = Product::factory()->create([
            'commerce_id' => $commerce2->id,
            'base_price' => 15.00,
            'stock' => 10,
            'available' => true
        ]);

        $this->cartService->addItem($profile, $product1->id, 1, 'retail');
        $this->cartService->addItem($profile, $product2->id, 1, 'retail');

        $cart = $this->cartService->getCart($profile);

        // 2 comercios = $10 * 2 = $20 de envío
        $this->assertEquals(20.00, $cart['summary']['shipping']);
    }

    /** @test */
    public function aplica_descuento_de_envio_por_compra_grande()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create([
            'base_price' => 150.00,
            'stock' => 10,
            'available' => true
        ]);

        $this->cartService->addItem($profile, $product->id, 1, 'retail');

        $cart = $this->cartService->getCart($profile);

        // Subtotal > 100, envío con 50% descuento: $10 * 0.5 = $5
        $this->assertEquals(5.00, $cart['summary']['shipping']);
    }

    /** @test */
    public function valida_stock_de_todo_el_carrito()
    {
        $profile = Profile::factory()->buyer()->create();
        $product1 = Product::factory()->create(['stock' => 5, 'available' => true]);
        $product2 = Product::factory()->create(['stock' => 10, 'available' => true]);

        $this->cartService->addItem($profile, $product1->id, 3, 'retail');
        $this->cartService->addItem($profile, $product2->id, 5, 'retail');

        // Reducir stock del producto2 después de agregarlo
        $product2->update(['stock' => 2]);

        $validation = $this->cartService->validateCartStock($profile);

        $this->assertFalse($validation['valid']);
        $this->assertCount(1, $validation['errors']);
        $this->assertEquals($product2->id, $validation['errors'][0]['product_id']);
    }

    /** @test */
    public function detecta_productos_no_disponibles_en_validacion()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create(['stock' => 10, 'available' => true]);

        $this->cartService->addItem($profile, $product->id, 2, 'retail');

        // Hacer el producto no disponible
        $product->update(['available' => false]);

        $validation = $this->cartService->validateCartStock($profile);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('no está disponible', $validation['errors'][0]['message']);
    }

    /** @test */
    public function carrito_vacio_retorna_true_en_isEmpty()
    {
        $profile = Profile::factory()->buyer()->create();

        $isEmpty = $this->cartService->isEmpty($profile);

        $this->assertTrue($isEmpty);
    }

    /** @test */
    public function carrito_con_items_retorna_false_en_isEmpty()
    {
        $profile = Profile::factory()->buyer()->create();
        $product = Product::factory()->create(['stock' => 10, 'available' => true]);

        $this->cartService->addItem($profile, $product->id, 1, 'retail');

        $isEmpty = $this->cartService->isEmpty($profile);

        $this->assertFalse($isEmpty);
    }

    /** @test */
    public function getItemsCount_retorna_suma_correcta()
    {
        $profile = Profile::factory()->buyer()->create();
        $product1 = Product::factory()->create(['stock' => 10, 'available' => true]);
        $product2 = Product::factory()->create(['stock' => 10, 'available' => true]);

        $this->cartService->addItem($profile, $product1->id, 3, 'retail');
        $this->cartService->addItem($profile, $product2->id, 2, 'retail');

        $count = $this->cartService->getItemsCount($profile);

        $this->assertEquals(5, $count); // 3 + 2
    }
}

