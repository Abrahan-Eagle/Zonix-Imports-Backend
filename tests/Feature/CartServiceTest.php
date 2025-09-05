<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = new CartService();
    }

    public function test_add_to_cart_new_product()
    {
        $productData = [
            'product_id' => 1,
            'quantity' => 2,
        ];

        $cart = $this->cartService->addToCart($productData);

        $this->assertCount(1, $cart);
        $this->assertEquals(2, $cart[0]['quantity']);
    }

    public function test_add_to_cart_existing_product()
    {
        // Agregar producto por primera vez
        $this->cartService->addToCart(['product_id' => 1, 'quantity' => 2]);

        // Agregar el mismo producto de nuevo
        $cart = $this->cartService->addToCart(['product_id' => 1, 'quantity' => 3]);

        $this->assertCount(1, $cart);
        $this->assertEquals(5, $cart[0]['quantity']); // 2 + 3
    }

    public function test_update_quantity()
    {
        // Agregar producto al carrito
        $this->cartService->addToCart(['product_id' => 1, 'quantity' => 2]);

        // Actualizar cantidad
        $cart = $this->cartService->updateQuantity(1, 5);

        $this->assertEquals(5, $cart[0]['quantity']);
    }

    public function test_remove_from_cart()
    {
        // Agregar productos al carrito
        $this->cartService->addToCart(['product_id' => 1, 'quantity' => 2]);
        $this->cartService->addToCart(['product_id' => 2, 'quantity' => 1]);

        // Remover un producto
        $cart = $this->cartService->removeFromCart(1);

        $this->assertCount(1, $cart);
        $this->assertEquals(2, $cart[0]['product_id']);
    }

    public function test_add_notes_to_cart()
    {
        // Agregar producto al carrito
        $this->cartService->addToCart(['product_id' => 1, 'quantity' => 2]);

        // Agregar notas
        $cart = $this->cartService->addNotes('Sin cebolla, por favor');

        $this->assertEquals('Sin cebolla, por favor', $cart['notes']);
    }

    public function test_clear_cart()
    {
        // Agregar productos al carrito
        $this->cartService->addToCart(['product_id' => 1, 'quantity' => 2]);

        // Limpiar carrito
        $cart = $this->cartService->clearCart();

        $this->assertEmpty($cart);
    }
} 