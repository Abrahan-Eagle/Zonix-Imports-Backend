<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'users']);
        Storage::fake('local');
    }

    /** @test */
    public function it_can_request_data_export()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/export-data', [
                'data_types' => ['profile', 'orders'],
                'format' => 'json'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'data_types',
                    'format',
                    'status',
                    'created_at',
                    'completed_at',
                ]
            ]);
    }

    /** @test */
    public function it_validates_data_types()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/export-data', [
                'data_types' => ['invalid_type'],
                'format' => 'json'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data_types.0']);
    }

    /** @test */
    public function it_validates_format()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/user/export-data', [
                'data_types' => ['profile'],
                'format' => 'invalid_format'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function it_can_get_export_status()
    {
        $exportId = '123e4567-e89b-12d3-a456-426614174000';

        $response = $this->actingAs($this->user)
            ->getJson("/api/user/export-status/{$exportId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'user_id',
                    'data_types',
                    'format',
                    'status',
                    'file_size',
                    'created_at',
                    'completed_at',
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_export()
    {
        $exportId = 'nonexistent-id';

        $response = $this->actingAs($this->user)
            ->getJson("/api/user/export-status/{$exportId}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_download_export()
    {
        $exportId = '123e4567-e89b-12d3-a456-426614174000';

        $response = $this->actingAs($this->user)
            ->getJson("/api/user/download-export/{$exportId}");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Content-Disposition');
    }

    /** @test */
    public function it_can_get_export_history()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/export-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'data_types',
                        'format',
                        'status',
                        'file_size',
                        'created_at',
                        'completed_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/user/export-data');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_supports_different_formats()
    {
        $formats = ['json', 'csv', 'pdf'];

        foreach ($formats as $format) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/user/export-data', [
                    'data_types' => ['profile'],
                    'format' => $format
                ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'format' => $format
                    ]
                ]);
        }
    }

    /** @test */
    public function it_includes_all_requested_data_types()
    {
        $dataTypes = ['profile', 'orders', 'activity', 'reviews'];

        $response = $this->actingAs($this->user)
            ->postJson('/api/user/export-data', [
                'data_types' => $dataTypes,
                'format' => 'json'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'data_types' => $dataTypes
                ]
            ]);
    }
}
