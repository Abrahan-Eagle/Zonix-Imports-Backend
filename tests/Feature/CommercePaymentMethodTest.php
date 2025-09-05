<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Bank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercePaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_commerce_can_add_payment_method_and_no_duplicates()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        $bank = Bank::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear método de pago
        $response = $this->postJson('/api/payments/methods', [
            'type' => 'mobile_payment',
            'bank_id' => $bank->id,
            'account_number' => '01021234567890123456',
            'phone' => '04141234567',
            'owner_name' => 'Comercio Prueba',
            'owner_id' => '12345678',
            'is_default' => true
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);

        // Intentar duplicado
        $response = $this->postJson('/api/payments/methods', [
            'type' => 'mobile_payment',
            'bank_id' => $bank->id,
            'account_number' => '01021234567890123456',
            'phone' => '04141234567',
            'owner_name' => 'Comercio Prueba',
            'owner_id' => '12345678',
            'is_default' => false
        ]);
        $response->assertStatus(422)->assertJson(['success' => false]);
    }
} 