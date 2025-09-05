<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'users']);
    }

    /** @test */
    public function it_can_get_user_activity_history()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/activity-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'activity_type',
                        'description',
                        'metadata',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_activity_by_type()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/activity-history?activity_type=login');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $data = $response->json('data');
        foreach ($data as $activity) {
            $this->assertEquals('login', $activity['activity_type']);
        }
    }

    /** @test */
    public function it_can_filter_activity_by_date_range()
    {
        $startDate = now()->subDays(5)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/user/activity-history?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    /** @test */
    public function it_validates_invalid_activity_type()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/activity-history?activity_type=invalid_type');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['activity_type']);
    }

    /** @test */
    public function it_validates_invalid_date_range()
    {
        $startDate = now()->toDateString();
        $endDate = now()->subDays(5)->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/user/activity-history?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /** @test */
    public function it_can_get_activity_stats()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/activity-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_activities',
                    'this_month',
                    'this_week',
                    'this_day',
                    'activity_breakdown',
                    'most_active_day',
                    'average_activities_per_day',
                ]
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/user/activity-history');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_supports_pagination()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user/activity-history?page=2&limit=5');

        $response->assertStatus(200)
            ->assertJson([
                'pagination' => [
                    'current_page' => 2,
                    'per_page' => 5,
                ]
            ]);
    }
}
