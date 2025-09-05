<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Commerce;
use App\Models\Order;
use App\Models\Profile;
use App\Models\DeliveryAgent;
use App\Models\DeliveryCompany;
use App\Models\Product;
use Laravel\Sanctum\Sanctum;

class CompleteRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tests para el rol ADMIN
     */
    public function test_admin_can_access_all_system_features()
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        // Crear datos de prueba
        $users = User::factory()->count(3)->create();
        $profile = Profile::factory()->create();
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        $orders = Order::factory()->count(3)->create();

        // Verificar acceso a listar usuarios
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(200);

        // Verificar acceso a listar comercios
        $response = $this->getJson('/api/admin/commerces');
        $response->assertStatus(200);

        // Verificar acceso a listar órdenes
        $response = $this->getJson('/api/admin/orders');
        $response->assertStatus(200);
    }

    /**
     * Tests para el rol USERS (Clientes)
     */
    public function test_users_can_access_client_features()
    {
        $user = User::factory()->buyer()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        // Verificar acceso a productos
        $response = $this->getJson('/api/buyer/products');
        $response->assertStatus(200);

        // Verificar acceso a restaurantes
        $response = $this->getJson('/api/buyer/restaurants');
        $response->assertStatus(200);

        // Verificar acceso a carrito
        $response = $this->getJson('/api/buyer/cart');
        $response->assertStatus(200);

        // Verificar acceso a órdenes propias
        $response = $this->getJson('/api/buyer/orders');
        $response->assertStatus(200);
    }

    public function test_users_can_create_orders()
    {
        $user = User::factory()->buyer()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerceProfile = Profile::factory()->create();
        $commerce = Commerce::factory()->create(['profile_id' => $commerceProfile->id]);
        $product = Product::factory()->create(['commerce_id' => $commerce->id]);
        
        Sanctum::actingAs($user);

        // Crear orden con datos válidos
        $orderData = [
            'products' => [
                [
                    'id' => $product->id,
                    'quantity' => 2
                ]
            ],
            'commerce_id' => $commerce->id,
            'delivery_type' => 'delivery',
            'total' => 25.99
        ];

        $response = $this->postJson('/api/buyer/orders', $orderData);
        $response->assertStatus(201);
    }

    public function test_users_can_manage_profile()
    {
        $user = User::factory()->buyer()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        // Verificar acceso al perfil
        $response = $this->getJson('/api/profiles/' . $profile->id);
        $response->assertStatus(200);

        // Verificar que puede actualizar perfil
        $response = $this->postJson('/api/profiles/' . $profile->id, [
            'firstName' => 'Nuevo Nombre',
            'lastName' => 'Nuevo Apellido',
            'date_of_birth' => '1990-01-01',
            'maritalStatus' => 'single',
            'sex' => 'M'
        ]);
        $response->assertStatus(200);
    }

    /**
     * Tests para el rol COMMERCE
     */
    public function test_commerce_can_access_restaurant_features()
    {
        $commerceUser = User::factory()->commerce()->create();
        $profile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        Sanctum::actingAs($commerceUser);

        // Verificar acceso a órdenes del comercio
        $response = $this->getJson('/api/commerce/orders');
        $response->assertStatus(200);

        // Verificar acceso a productos del comercio
        $response = $this->getJson('/api/commerce/products');
        $response->assertStatus(200);

        // Verificar acceso al dashboard
        $response = $this->getJson('/api/commerce/dashboard');
        $response->assertStatus(200);
    }

    public function test_commerce_can_manage_orders()
    {
        $commerceUser = User::factory()->commerce()->create();
        $profile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        Sanctum::actingAs($commerceUser);

        // Crear orden para este comercio
        $order = Order::factory()->create([
            'commerce_id' => $commerce->id,
            'status' => 'pending_payment'
        ]);

        // Verificar que puede actualizar estado de la orden
        $response = $this->putJson("/api/commerce/orders/{$order->id}/status", [
            'status' => 'preparing'
        ]);
        $response->assertStatus(200);
    }

    public function test_commerce_can_manage_products()
    {
        $commerceUser = User::factory()->commerce()->create();
        $profile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        Sanctum::actingAs($commerceUser);

        // Verificar que puede crear productos
        $productData = [
            'name' => 'Nuevo Producto',
            'description' => 'Descripción del producto',
            'price' => 15.99,
            'category' => 'Comida',
            'available' => true
        ];

        $response = $this->postJson('/api/commerce/products', $productData);
        $response->assertStatus(201);
    }

    /**
     * Tests para el rol DELIVERY_AGENT
     */
    public function test_delivery_agent_can_access_agent_features()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create();
        $profile = Profile::factory()->create(['user_id' => $deliveryUser->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $profile->id]);
        Sanctum::actingAs($deliveryUser);

        // Verificar acceso a órdenes asignadas
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(200);
    }

    public function test_delivery_agent_can_update_order_status()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create();
        $profile = Profile::factory()->create(['user_id' => $deliveryUser->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $profile->id]);
        Sanctum::actingAs($deliveryUser);

        // Crear orden asignada al repartidor
        $order = Order::factory()->create([
            'status' => 'on_way'
        ]);

        // Crear la relación order_delivery
        \App\Models\OrderDelivery::create([
            'order_id' => $order->id,
            'agent_id' => $deliveryAgent->id,
            'status' => 'assigned',
            'costo_envio' => 10.0
        ]);

        // Verificar que puede marcar orden como entregada
        $response = $this->patchJson("/api/delivery/orders/{$order->id}/status", [
            'status' => 'delivered'
        ]);
        $response->assertStatus(200);
    }

    /**
     * Tests para el rol DELIVERY (alias de delivery_agent)
     */
    public function test_delivery_role_has_same_permissions_as_delivery_agent()
    {
        $deliveryUser = User::factory()->create(['role' => 'delivery']);
        $profile = Profile::factory()->create(['user_id' => $deliveryUser->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $profile->id]);
        Sanctum::actingAs($deliveryUser);

        // Verificar que tiene los mismos permisos que delivery_agent
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(200);
    }

    /**
     * Tests de autorización y permisos
     */
    public function test_users_cannot_access_admin_features()
    {
        $user = User::factory()->buyer()->create();
        Sanctum::actingAs($user);

        // Intentar acceder a features de admin
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(403);

        $response = $this->getJson('/api/admin/commerces');
        $response->assertStatus(403);
    }

    public function test_commerce_cannot_access_delivery_features()
    {
        $commerceUser = User::factory()->commerce()->create();
        Sanctum::actingAs($commerceUser);

        // Intentar acceder a features de delivery
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(403);
    }

    public function test_delivery_cannot_access_commerce_features()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create();
        Sanctum::actingAs($deliveryUser);

        // Intentar acceder a features de commerce
        $response = $this->getJson('/api/commerce/orders');
        $response->assertStatus(403);

        $response = $this->getJson('/api/commerce/products');
        $response->assertStatus(403);
    }

    /**
     * Tests de autenticación
     */
    public function test_unauthenticated_users_cannot_access_protected_endpoints()
    {
        // Sin autenticación
        $response = $this->getJson('/api/buyer/products');
        $response->assertStatus(401);

        $response = $this->getJson('/api/commerce/orders');
        $response->assertStatus(401);

        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(401);
    }

    /**
     * Tests de transiciones de estado
     */
    public function test_order_status_transitions_by_role()
    {
        // Test para commerce
        $commerceUser = User::factory()->commerce()->create();
        $profile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        Sanctum::actingAs($commerceUser);

        $order = Order::factory()->create([
            'commerce_id' => $commerce->id,
            'status' => 'pending_payment'
        ]);

        // Commerce puede cambiar de pendiente a preparando
        $response = $this->putJson("/api/commerce/orders/{$order->id}/status", [
            'status' => 'preparing'
        ]);
        $response->assertStatus(200);

        // Test para delivery
        $deliveryUser = User::factory()->deliveryAgent()->create();
        $deliveryProfile = Profile::factory()->create(['user_id' => $deliveryUser->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $deliveryProfile->id]);
        Sanctum::actingAs($deliveryUser);

        $order->update([
            'status' => 'on_way'
        ]);

        // Crear la relación order_delivery
        \App\Models\OrderDelivery::create([
            'order_id' => $order->id,
            'agent_id' => $deliveryAgent->id,
            'status' => 'assigned',
            'costo_envio' => 10.0
        ]);

        // Delivery puede cambiar de en_camino a entregado
        $response = $this->patchJson("/api/delivery/orders/{$order->id}/status", [
            'status' => 'delivered'
        ]);
        $response->assertStatus(200);
    }

    /**
     * Tests de verificación de roles
     */
    public function test_user_has_correct_role_after_creation()
    {
        $user = User::factory()->buyer()->create();
        $this->assertEquals('users', $user->role);

        $commerceUser = User::factory()->commerce()->create();
        $this->assertEquals('commerce', $commerceUser->role);

        $deliveryUser = User::factory()->deliveryAgent()->create();
        $this->assertEquals('delivery', $deliveryUser->role);

        $admin = User::factory()->admin()->create();
        $this->assertEquals('admin', $admin->role);
    }

    /**
     * Tests de middleware de roles
     */
    public function test_role_middleware_works_correctly()
    {
        // Test que un usuario con rol 'users' no puede acceder a rutas de commerce
        $user = User::factory()->buyer()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/commerce/dashboard');
        $response->assertStatus(403);

        // Test que un usuario con rol 'commerce' no puede acceder a rutas de delivery
        $commerceUser = User::factory()->commerce()->create();
        Sanctum::actingAs($commerceUser);

        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(403);

        // Test que un usuario con rol 'delivery' no puede acceder a rutas de admin
        $deliveryUser = User::factory()->deliveryAgent()->create();
        Sanctum::actingAs($deliveryUser);

        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(403);
    }
} 