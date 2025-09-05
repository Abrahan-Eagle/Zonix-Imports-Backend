<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PrivacyControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'users']);
    }

    /** @test */
    public function it_can_get_privacy_settings()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/privacy-settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_id',
                    'profile_visibility',
                    'order_history_visibility',
                    'activity_visibility',
                    'marketing_emails',
                    'push_notifications',
                    'location_sharing',
                    'data_analytics',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    /** @test */
    public function it_can_update_privacy_settings()
    {
        $settings = [
            'profile_visibility' => false,
            'order_history_visibility' => true,
            'activity_visibility' => false,
            'marketing_emails' => false,
            'push_notifications' => true,
            'location_sharing' => true,
            'data_analytics' => false,
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/user/privacy-settings', $settings);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Configuración actualizada correctamente'
            ])
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'profile_visibility',
                    'order_history_visibility',
                    'activity_visibility',
                    'marketing_emails',
                    'push_notifications',
                    'location_sharing',
                    'data_analytics',
                ]
            ]);

        // Verificar que los valores se actualizaron correctamente
        $data = $response->json('data');
        foreach ($settings as $key => $value) {
            $this->assertEquals($value, $data[$key]);
        }
    }

    /** @test */
    public function it_validates_privacy_settings()
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user/privacy-settings', [
                'profile_visibility' => 'invalid_value',
                'marketing_emails' => 'not_boolean',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['profile_visibility', 'marketing_emails']);
    }

    /** @test */
    public function it_can_get_privacy_policy()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/privacy-policy');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'version',
                    'last_updated',
                    'content',
                ]
            ]);
    }

    /** @test */
    public function it_can_get_terms_of_service()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/terms-of-service');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'version',
                    'last_updated',
                    'content',
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/user/privacy-settings');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_accepts_partial_updates()
    {
        $partialSettings = [
            'profile_visibility' => false,
            'marketing_emails' => false,
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/user/privacy-settings', $partialSettings);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    /** @test */
    public function it_preserves_existing_settings_on_partial_update()
    {
        // Primero obtener la configuración actual
        $currentResponse = $this->actingAs($this->user)
            ->getJson('/api/user/privacy-settings');
        
        $currentSettings = $currentResponse->json('data');

        // Actualizar solo una configuración
        $updateResponse = $this->actingAs($this->user)
            ->putJson('/api/user/privacy-settings', [
                'profile_visibility' => !$currentSettings['profile_visibility']
            ]);

        $updateResponse->assertStatus(200);

        $updatedSettings = $updateResponse->json('data');

        // Verificar que solo cambió la configuración especificada
        $this->assertEquals(!$currentSettings['profile_visibility'], $updatedSettings['profile_visibility']);
        
        // Verificar que las otras configuraciones se mantuvieron igual
        foreach ($currentSettings as $key => $value) {
            if (!in_array($key, ['profile_visibility', 'updated_at', 'created_at'])) {
                $this->assertEquals($value, $updatedSettings[$key]);
            }
        }
    }
}
