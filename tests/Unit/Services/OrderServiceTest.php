<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\OrderService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\User;
use App\Models\Address;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $orderService;
    protected $profile;
    protected $commerce;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();

        $user = User::factory()->create();
        $this->profile = Profile::factory()->create(['user_id' => $user->id]);
        $this->commerce = Commerce::factory()->create();
    }

    /** @test */
    public function puede_obtener_ordenes_del_comprador()
    {
        Order::factory()->count(3)->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id
        ]);

        $orders = $this->orderService->getBuyerOrders($this->profile);

        $this->assertCount(3, $orders);
    }

    /** @test */
    public function puede_filtrar_ordenes_por_status()
    {
        Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'paid'
        ]);

        Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id,
            'status' => 'pending_payment'
        ]);

        $orders = $this->orderService->getBuyerOrders($this->profile, ['status' => 'paid']);

        $this->assertCount(1, $orders);
        $this->assertEquals('paid', $orders->first()->status);
    }

    /** @test */
    public function puede_obtener_detalle_de_orden()
    {
        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id
        ]);

        OrderItem::factory()->create(['order_id' => $order->id]);

        $detail = $this->orderService->getBuyerOrderDetail($order->id, $this->profile);

        $this->assertEquals($order->id, $detail->id);
        $this->assertCount(1, $detail->orderItems);
    }

    /** @test */
    public function lanza_excepcion_si_orden_no_pertenece_al_comprador()
    {
        $otherProfile = Profile::factory()->create();
        $order = Order::factory()->create([
            'profile_id' => $otherProfile->id,
            'commerce_id' => $this->commerce->id
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No tienes permiso para ver esta orden');

        $this->orderService->getBuyerOrderDetail($order->id, $this->profile);
    }

    /** @test */
    public function puede_obtener_ordenes_del_vendedor()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        Order::factory()->count(2)->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $sellerCommerce->id
        ]);

        $orders = $this->orderService->getSellerOrders($sellerProfile);

        $this->assertCount(2, $orders);
    }

    /** @test */
    public function lanza_excepcion_si_seller_no_tiene_comercio()
    {
        $sellerProfile = Profile::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No tienes un comercio asociado');

        $this->orderService->getSellerOrders($sellerProfile);
    }

    /** @test */
    public function puede_actualizar_estado_de_orden()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $sellerCommerce->id,
            'status' => 'paid'
        ]);

        $updated = $this->orderService->updateOrderStatus(
            $order->id,
            $sellerProfile,
            'preparing',
            'TRACK123'
        );

        $this->assertEquals('preparing', $updated->status);
        $this->assertEquals('TRACK123', $updated->tracking_number);
    }

    /** @test */
    public function lanza_excepcion_si_transicion_de_estado_invalida()
    {
        $sellerUser = User::factory()->create();
        $sellerProfile = Profile::factory()->create(['user_id' => $sellerUser->id]);
        $sellerCommerce = Commerce::factory()->create(['profile_id' => $sellerProfile->id]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $sellerCommerce->id,
            'status' => 'pending_payment'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No se puede cambiar de 'pending_payment' a 'delivered'");

        $this->orderService->updateOrderStatus($order->id, $sellerProfile, 'delivered');
    }

    /** @test */
    public function puede_obtener_tracking_de_orden()
    {
        $city = City::factory()->create();
        $address = Address::factory()->create([
            'profile_id' => $this->profile->id,
            'city_id' => $city->id
        ]);

        $order = Order::factory()->create([
            'profile_id' => $this->profile->id,
            'commerce_id' => $this->commerce->id,
            'shipping_address_id' => $address->id,
            'status' => 'preparing',
            'tracking_number' => 'TRACK456'
        ]);

        $tracking = $this->orderService->getOrderTracking($order->id, $this->profile);

        $this->assertEquals($order->id, $tracking['order_id']);
        $this->assertEquals('preparing', $tracking['current_status']);
        $this->assertEquals('TRACK456', $tracking['tracking_number']);
        $this->assertIsArray($tracking['timeline']);
    }
}

