<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function testIndex()
    {
        $response = $this->actingAs($this->user, 'sanctum')
                        ->get('/api/profiles');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => [
                         'id', 'user_id', 'firstName', 'middleName', 'lastName', 'secondLastName', 'photo_users', 'date_of_birth', 'maritalStatus', 'sex'
                     ]
                 ]);
    }

    public function testStore()
    {
        $data = [
            'user_id' => $this->user->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'date_of_birth' => '1985-05-15',
            'maritalStatus' => 'single',
            'sex' => 'M'
        ];

        $response = $this->actingAs($this->user, 'sanctum')
                        ->post('/api/profiles', $data);
        $response->assertStatus(201)
                 ->assertJson(['message' => 'Perfil creado exitosamente.']);
    }

    public function testShow()
    {
        $profile = Profile::factory()->create(['user_id' => $this->user->id]);
        $response = $this->actingAs($this->user, 'sanctum')
                        ->get("/api/profiles/{$profile->id}");
        $response->assertStatus(200)
                 ->assertJson(['id' => $profile->id]);
    }

    public function testUpdate()
    {
        $profile = Profile::factory()->create(['user_id' => $this->user->id]);
        $data = [
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'date_of_birth' => '1985-05-15',
            'maritalStatus' => 'single',
            'sex' => 'M'
        ];

        $response = $this->actingAs($this->user, 'sanctum')
                        ->post("/api/profiles/{$profile->id}", $data);
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Perfil actualizado exitosamente.']);
    }

    public function testDestroy()
    {
        $profile = Profile::factory()->create(['user_id' => $this->user->id]);
        $response = $this->actingAs($this->user, 'sanctum')
                        ->delete("/api/profiles/{$profile->id}");
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Perfil eliminado exitosamente']);
    }
}
