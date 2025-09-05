<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class AccountDeletionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'users',
            'password' => Hash::make('password123')
        ]);
        
        // Limpiar el estado estático del controlador
        $this->clearMockDeletionRequests();
    }

    protected function tearDown(): void
    {
        // Limpiar el estado estático después de cada test
        $this->clearMockDeletionRequests();
        parent::tearDown();
    }

    private function clearMockDeletionRequests()
    {
        // Usar reflection para acceder y limpiar la variable estática privada
        $reflection = new \ReflectionClass(\App\Http\Controllers\Buyer\AccountDeletionController::class);
        $property = $reflection->getProperty('mockDeletionRequest');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    /** @test */
    public function it_can_request_account_deletion()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/request-deletion', [
                'reason' => 'Ya no uso la aplicación',
                'feedback' => 'La aplicación funciona bien, pero ya no la necesito',
                'immediate' => false
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'deletion_id',
                    'scheduled_for',
                    'immediate',
                ]
            ]);
    }

    /** @test */
    public function it_validates_deletion_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/request-deletion', [
                'reason' => '', // Campo requerido vacío
                'feedback' => str_repeat('a', 1001), // Demasiado largo
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason', 'feedback']);
    }

    /** @test */
    public function it_prevents_duplicate_deletion_requests()
    {
        // Primera solicitud
        $this->actingAs($this->user)
            ->postJson('/api/user/request-deletion', [
                'reason' => 'Primera solicitud',
                'immediate' => false
            ]);

        // Segunda solicitud (debería fallar)
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/request-deletion', [
                'reason' => 'Segunda solicitud',
                'immediate' => false
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Ya tienes una solicitud de eliminación pendiente'
            ]);
    }

    /** @test */
    public function it_can_confirm_account_deletion()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/confirm-deletion', [
                'confirmation_code' => 'ABC123',
                'password' => 'password123'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cuenta eliminada correctamente'
            ])
            ->assertJsonStructure([
                'data' => [
                    'deleted_at',
                ]
            ]);
    }

    /** @test */
    public function it_validates_confirmation_code()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/confirm-deletion', [
                'confirmation_code' => 'INVALID',
                'password' => 'password123'
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Código de confirmación inválido'
            ]);
    }

    /** @test */
    public function it_validates_password_for_confirmation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/confirm-deletion', [
                'confirmation_code' => 'ABC123',
                'password' => 'wrongpassword'
            ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Contraseña incorrecta'
            ]);
    }

    /** @test */
    public function it_can_cancel_deletion_request()
    {
        // Primero crear una solicitud
        $this->actingAs($this->user)
            ->postJson('/api/user/request-deletion', [
                'reason' => 'Solicitud para cancelar',
                'immediate' => false
            ]);

        // Luego cancelarla
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/user/cancel-deletion');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Solicitud de eliminación cancelada correctamente'
            ]);
    }

    /** @test */
    public function it_cannot_cancel_nonexistent_deletion_request()
    {
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/user/cancel-deletion');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'No hay una solicitud de eliminación pendiente'
            ]);
    }

    /** @test */
    public function it_can_get_deletion_status()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/deletion-status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'has_pending_request',
                    'status',
                    'requested_at',
                    'scheduled_for',
                    'reason',
                    'immediate',
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/user/request-deletion');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_supports_immediate_deletion()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/request-deletion', [
                'reason' => 'Eliminación inmediata',
                'immediate' => true
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'immediate' => true
                ]
            ]);
    }

    /** @test */
    public function it_handles_long_reason_text()
    {
        $longReason = str_repeat('a', 500);

        $response = $this->actingAs($this->user)
            ->postJson('/api/user/request-deletion', [
                'reason' => $longReason,
                'immediate' => false
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }
} 