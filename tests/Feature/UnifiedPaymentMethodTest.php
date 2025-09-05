<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Bank;
use App\Models\PaymentMethod;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\DeliveryAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_payment_methods()
    {
        $user = User::factory()->create(['role' => 'users']);
        $bank = Bank::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear primer método de pago
        $response = $this->postJson('/api/payment-methods', [
            'type' => 'mobile_payment',
            'bank_id' => $bank->id,
            'account_number' => '01021234567890123456',
            'phone' => '04141234567',
            'owner_name' => 'Juan Pérez',
            'owner_id' => '12345678',
            'is_default' => true
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $id = $response->json('data.id');

        // Listar métodos de pago
        $response = $this->getJson('/api/payment-methods');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertCount(1, $response->json('data'));

        // Actualizar método de pago
        $response = $this->putJson("/api/payment-methods/$id", [
            'phone' => '04140000000',
            'is_default' => false
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertEquals('04140000000', $response->json('data.phone'));

        // Establecer como predeterminado
        $response = $this->patchJson("/api/payment-methods/$id/default");
        $response->assertStatus(200)->assertJson(['success' => true]);

        // Crear segundo método de pago para poder eliminar el primero
        $response = $this->postJson('/api/payment-methods', [
            'type' => 'card',
            'brand' => 'Visa',
            'last4' => '1234',
            'exp_month' => 12,
            'exp_year' => 2025,
            'cardholder_name' => 'Juan Pérez',
            'is_default' => false
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);

        // Ahora sí eliminar el primer método de pago
        $response = $this->deleteJson("/api/payment-methods/$id");
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('payment_methods', ['id' => $id]);
    }

    public function test_commerce_can_manage_payment_methods()
    {
        $user = User::factory()->create(['role' => 'commerce']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        $bank = Bank::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear método de pago para comercio
        $response = $this->postJson('/api/payment-methods', [
            'type' => 'card',
            'brand' => 'Visa',
            'last4' => '1234',
            'exp_month' => 12,
            'exp_year' => 2025,
            'cardholder_name' => 'Comercio Test',
            'is_default' => true
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);

        // Verificar que se creó con la relación polimórfica correcta
        $this->assertDatabaseHas('payment_methods', [
            'payable_type' => 'App\\Models\\User',
            'payable_id' => $user->id,
            'type' => 'card',
            'brand' => 'Visa'
        ]);
    }

    public function test_delivery_agent_can_manage_payment_methods()
    {
        $user = User::factory()->create(['role' => 'delivery']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $profile->id]);
        $bank = Bank::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear método de pago para repartidor
        $response = $this->postJson('/api/payment-methods', [
            'type' => 'bank_transfer',
            'bank_id' => $bank->id,
            'account_number' => '01021234567890123456',
            'owner_name' => 'Repartidor Test',
            'owner_id' => '87654321',
            'is_default' => true
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);

        // Verificar que se creó con la relación polimórfica correcta
        $this->assertDatabaseHas('payment_methods', [
            'payable_type' => 'App\\Models\\User',
            'payable_id' => $user->id,
            'type' => 'bank_transfer',
            'owner_name' => 'Repartidor Test'
        ]);
    }

    public function test_cannot_create_duplicate_payment_methods()
    {
        $user = User::factory()->create(['role' => 'users']);
        $bank = Bank::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear primer método de pago
        $response = $this->postJson('/api/payment-methods', [
            'type' => 'mobile_payment',
            'bank_id' => $bank->id,
            'phone' => '04141234567',
            'owner_name' => 'Juan Pérez',
            'is_default' => true
        ]);
        $response->assertStatus(201);

        // Intentar crear duplicado
        $response = $this->postJson('/api/payment-methods', [
            'type' => 'mobile_payment',
            'bank_id' => $bank->id,
            'phone' => '04141234567',
            'owner_name' => 'Juan Pérez',
            'is_default' => false
        ]);
        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_cannot_delete_only_payment_method()
    {
        $user = User::factory()->create(['role' => 'users']);
        $bank = Bank::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear único método de pago
        $response = $this->postJson('/api/payment-methods', [
            'type' => 'mobile_payment',
            'bank_id' => $bank->id,
            'phone' => '04141234567',
            'owner_name' => 'Juan Pérez',
            'is_default' => true
        ]);
        $response->assertStatus(201);
        $id = $response->json('data.id');

        // Intentar eliminar el único método
        $response = $this->deleteJson("/api/payment-methods/$id");
        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_can_get_available_payment_methods()
    {
        $response = $this->getJson('/api/available-payment-methods');
        $response->assertStatus(200)->assertJson(['success' => true]);
        
        $methods = $response->json('data');
        $this->assertIsArray($methods);
        $this->assertGreaterThan(0, count($methods));
        
        // Verificar que tiene los campos esperados
        $firstMethod = $methods[0];
        $this->assertArrayHasKey('type', $firstMethod);
        $this->assertArrayHasKey('name', $firstMethod);
        $this->assertArrayHasKey('description', $firstMethod);
        $this->assertArrayHasKey('icon', $firstMethod);
        $this->assertArrayHasKey('enabled', $firstMethod);
    }

    public function test_payment_method_scopes_work_correctly()
    {
        $user = User::factory()->create(['role' => 'users']);
        $bank = Bank::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear métodos de pago activos e inactivos
        $activeMethod = $user->paymentMethods()->create([
            'type' => 'card',
            'brand' => 'Visa',
            'is_active' => true,
            'is_default' => true
        ]);

        $inactiveMethod = $user->paymentMethods()->create([
            'type' => 'mobile_payment',
            'is_active' => false,
            'is_default' => false
        ]);

        // Verificar scope activo
        $activeMethods = $user->paymentMethods()->active()->get();
        $this->assertCount(1, $activeMethods);
        $this->assertEquals($activeMethod->id, $activeMethods->first()->id);

        // Verificar scope por defecto
        $defaultMethods = $user->paymentMethods()->default()->get();
        $this->assertCount(1, $defaultMethods);
        $this->assertEquals($activeMethod->id, $defaultMethods->first()->id);

        // Verificar scope por tipo
        $cardMethods = $user->paymentMethods()->byType('card')->get();
        $this->assertCount(1, $cardMethods);
        $this->assertEquals($activeMethod->id, $cardMethods->first()->id);
    }
} 