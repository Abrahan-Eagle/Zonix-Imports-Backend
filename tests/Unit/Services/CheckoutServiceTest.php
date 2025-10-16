<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CheckoutService;
use App\Models\Profile;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Commerce;
use App\Models\User;
use App\Models\City;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $checkoutService;
    protected $profile;
    protected $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checkoutService = new CheckoutService();

        // Crear user, profile y dirección para tests
        $user = User::factory()->create();
        $this->profile = Profile::factory()->create([
            'user_id' => $user->id
        ]);
        
        $city = City::factory()->create();
        $this->address = Address::factory()->create([
            'profile_id' => $this->profile->id,
            'city_id' => $city->id
        ]);
    }

    /** @test */
    public function puede_obtener_resumen_del_checkout_con_carrito_vacio()
    {
        $summary = $this->checkoutService->getCheckoutSummary($this->profile);

        $this->assertFalse($summary['valid']);
        $this->assertEquals('El carrito está vacío', $summary['message']);
        $this->assertEquals(0, $summary['summary']['subtotal']);
    }

    /** @test */
    public function puede_obtener_resumen_del_checkout_con_items()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $summary = $this->checkoutService->getCheckoutSummary($this->profile);

        $this->assertTrue($summary['valid']);
        $this->assertEquals(100.00, $summary['summary']['subtotal']);
        $this->assertEquals(5.00, $summary['summary']['shipping']);
        $this->assertEquals(0, $summary['summary']['discount']);
        $this->assertEquals(105.00, $summary['summary']['total']);
    }

    /** @test */
    public function aplica_descuento_en_envio_si_subtotal_mayor_a_100()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 60.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 60.00,
            'subtotal' => 120.00
        ]);

        $summary = $this->checkoutService->getCheckoutSummary($this->profile);

        $this->assertTrue($summary['valid']);
        $this->assertEquals(120.00, $summary['summary']['subtotal']);
        $this->assertEquals(5.00, $summary['summary']['shipping']);
        $this->assertEquals(2.50, $summary['summary']['discount']); // 50% de $5
        $this->assertEquals(122.50, $summary['summary']['total']);
    }

    /** @test */
    public function puede_iniciar_checkout_con_datos_validos()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $result = $this->checkoutService->initiateCheckout(
            $this->profile,
            $this->address->id,
            'delivery'
        );

        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('cart_items', $result);
        $this->assertArrayHasKey('shipping_address', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals('delivery', $result['delivery_type']);
        $this->assertEquals($commerce->id, $result['commerce_id']);
    }

    /** @test */
    public function lanza_excepcion_si_carrito_esta_vacio_al_iniciar_checkout()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('El carrito está vacío');

        $this->checkoutService->initiateCheckout(
            $this->profile,
            $this->address->id,
            'delivery'
        );
    }

    /** @test */
    public function lanza_excepcion_si_direccion_no_existe()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 50.00
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Dirección no encontrada');

        $this->checkoutService->initiateCheckout(
            $this->profile,
            999, // ID no existente
            'delivery'
        );
    }

    /** @test */
    public function lanza_excepcion_si_producto_no_disponible()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'available' => false // No disponible
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 50.00
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no está disponible');

        $this->checkoutService->initiateCheckout(
            $this->profile,
            $this->address->id,
            'delivery'
        );
    }

    /** @test */
    public function lanza_excepcion_si_stock_insuficiente()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'stock' => 5,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 10, // Más del stock disponible
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 500.00
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $this->checkoutService->initiateCheckout(
            $this->profile,
            $this->address->id,
            'delivery'
        );
    }

    /** @test */
    public function puede_confirmar_checkout_y_crear_orden()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $order = $this->checkoutService->confirmCheckout(
            $this->profile,
            $this->address->id,
            'delivery',
            null,
            'Test notes'
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($this->profile->id, $order->profile_id);
        $this->assertEquals($commerce->id, $order->commerce_id);
        $this->assertEquals('pending_payment', $order->status);
        $this->assertEquals(100.00, $order->subtotal);
        $this->assertEquals(5.00, $order->shipping_total);
        $this->assertEquals(105.00, $order->total);
        $this->assertEquals('Test notes', $order->notes);

        // Verificar que se crearon los order_items
        $this->assertCount(1, $order->orderItems);
        $this->assertEquals($product->id, $order->orderItems->first()->product_id);
        $this->assertEquals(2, $order->orderItems->first()->quantity);

        // Verificar que el carrito se vació
        $this->assertEquals(0, CartItem::where('profile_id', $this->profile->id)->count());

        // Verificar que se redujo el stock
        $product->refresh();
        $this->assertEquals(98, $product->stock);
    }

    /** @test */
    public function calcula_envio_correcto_para_multiples_comercios()
    {
        $commerce1 = Commerce::factory()->create();
        $commerce2 = Commerce::factory()->create();

        $product1 = Product::factory()->create([
            'commerce_id' => $commerce1->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        $product2 = Product::factory()->create([
            'commerce_id' => $commerce2->id,
            'base_price' => 60.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 50.00
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'modality' => 'retail',
            'unit_price' => 60.00,
            'subtotal' => 60.00
        ]);

        $summary = $this->checkoutService->getCheckoutSummary($this->profile);

        // 2 comercios = $5 x 2 = $10 de envío
        $this->assertEquals(10.00, $summary['summary']['shipping']);
        $this->assertEquals(110.00, $summary['summary']['subtotal']);
        $this->assertEquals(5.00, $summary['summary']['discount']); // 50% de $10 porque subtotal > $100
        $this->assertEquals(115.00, $summary['summary']['total']);
    }

    /** @test */
    public function shipping_es_cero_si_delivery_type_es_pickup()
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create([
            'commerce_id' => $commerce->id,
            'base_price' => 50.00,
            'stock' => 100,
            'available' => true
        ]);

        CartItem::factory()->create([
            'profile_id' => $this->profile->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'modality' => 'retail',
            'unit_price' => 50.00,
            'subtotal' => 100.00
        ]);

        $result = $this->checkoutService->initiateCheckout(
            $this->profile,
            $this->address->id,
            'pickup' // Pickup en lugar de delivery
        );

        $this->assertEquals(0, $result['summary']['shipping']);
        $this->assertEquals(100.00, $result['summary']['total']); // Sin envío
    }
}

