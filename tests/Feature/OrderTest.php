<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    public function test_user_can_create_upload_comprobante_and_cancel_order()
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'users']);
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        $product = Product::factory()->create();
        $this->actingAs($user, 'sanctum');

        // Crear orden
        $response = $this->postJson('/api/buyer/orders', [
            'commerce_id' => $commerce->id,
            'products' => [
                ['id' => $product->id, 'quantity' => 2]
            ],
            'delivery_type' => 'pickup',
            'total' => 100,
            'notes' => 'Sin cebolla',
            'delivery_address' => 'Calle 123'
        ]);
        $response->assertStatus(201)->assertJson(['success' => true]);
        $orderId = $response->json('data.id');

        // Subir comprobante
        $file = UploadedFile::fake()->image('comprobante.jpg');
        $response = $this->postJson("/api/buyer/orders/{$orderId}/payment-proof", [
            'payment_proof' => $file,
            'payment_method' => 'mobile_payment',
            'reference_number' => '123456'
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
        Storage::disk('public')->assertExists('payment_proofs/' . $file->hashName());

        // Cancelar orden
        $response = $this->postJson("/api/buyer/orders/{$orderId}/cancel", [
            'reason' => 'Cambio de planes'
        ]);
        $response->assertStatus(200)->assertJson(['success' => true]);
    }
} 