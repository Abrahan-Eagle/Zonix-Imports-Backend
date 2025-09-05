<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Commerce;
use App\Models\Product;
use App\Models\Review;
use App\Models\Profile;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $reviewService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reviewService = new ReviewService();
    }

    public function test_can_user_review_with_delivered_order()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create();
        
        // Crear un pedido entregado
        $order = Order::create([
            'profile_id' => $profile->id,
            'commerce_id' => $commerce->id,
            'delivery_type' => 'pickup',
            'status' => 'delivered',
            'total' => 50.00,
            'notes' => 'Test order'
        ]);

        $canReview = $this->reviewService->canUserReview($user->id, $order->id);

        $this->assertTrue($canReview);
    }

    public function test_cannot_user_review_without_delivered_order()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $commerce = Commerce::factory()->create();
        
        // Crear un pedido que no está entregado
        $order = Order::create([
            'profile_id' => $profile->id,
            'commerce_id' => $commerce->id,
            'delivery_type' => 'pickup',
            'status' => 'pending_payment',
            'total' => 50.00,
            'notes' => 'Test order'
        ]);

        $canReview = $this->reviewService->canUserReview($user->id, $order->id);

        $this->assertFalse($canReview);
    }

    public function test_create_review_successfully()
    {
        $commerce = Commerce::factory()->create();
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $order = Order::create([
            'profile_id' => $profile->id,
            'commerce_id' => $commerce->id,
            'delivery_type' => 'pickup',
            'status' => 'delivered',
            'total' => 50.00,
            'notes' => 'Test order'
        ]);
        $this->actingAs($user);
        $data = [
            'order_id' => $order->id,
            'type' => 'restaurant',
            'rating' => 5,
            'comment' => 'Excelente servicio'
        ];

        $result = $this->reviewService->createReview($data + ['user_id' => $user->id]);

        $this->assertInstanceOf(Review::class, $result);
        $this->assertEquals(5, $result->rating);
        $this->assertEquals('Excelente servicio', $result->comentario);
        $this->assertEquals($profile->id, $result->profile_id);
        $this->assertEquals('App\\Models\\Commerce', $result->reviewable_type);
        $this->assertEquals($commerce->id, $result->reviewable_id);
    }

    public function test_cannot_create_duplicate_review()
    {
        $commerce = Commerce::factory()->create();
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $order = Order::create([
            'profile_id' => $profile->id,
            'commerce_id' => $commerce->id,
            'delivery_type' => 'pickup',
            'status' => 'delivered',
            'total' => 50.00,
            'notes' => 'Test order'
        ]);
        $this->actingAs($user);
        $data = [
            'order_id' => $order->id,
            'type' => 'restaurant',
            'rating' => 5,
            'comment' => 'Excelente'
        ];

        // Crear primera calificación
        $this->reviewService->createReview($data + ['user_id' => $user->id]);

        // Intentar crear segunda calificación - debería fallar
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ya has calificado este elemento');
        
        $this->reviewService->createReview($data + ['user_id' => $user->id]);
    }

    public function test_get_average_rating()
    {
        $commerce = Commerce::factory()->create();
        
        // Crear 3 usuarios diferentes para evitar conflictos de reviews duplicados
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            $profile = Profile::factory()->create(['user_id' => $user->id]);
            $order = Order::create([
                'profile_id' => $profile->id,
                'commerce_id' => $commerce->id,
                'delivery_type' => 'pickup',
                'status' => 'delivered',
                'total' => 50.00,
                'notes' => 'Test order'
            ]);
            $this->actingAs($user);
            $data = [
                'order_id' => $order->id,
                'type' => 'restaurant',
                'rating' => $i === 0 ? 5 : ($i === 1 ? 3 : 4),
                'comment' => "Review {$i}",
                'user_id' => $user->id
            ];
            $this->reviewService->createReview($data);
        }
        
        $averageRating = $this->reviewService->getRestaurantAverageRating($commerce->id);
        $this->assertEquals(4.0, $averageRating); // (5 + 3 + 4) / 3 = 4
    }

    public function test_get_reviews()
    {
        $commerce = Commerce::factory()->create();
        
        // Crear 3 usuarios diferentes para evitar conflictos de reviews duplicados
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create();
            $profile = Profile::factory()->create(['user_id' => $user->id]);
            $order = Order::create([
                'profile_id' => $profile->id,
                'commerce_id' => $commerce->id,
                'delivery_type' => 'pickup',
                'status' => 'delivered',
                'total' => 50.00,
                'notes' => 'Test order'
            ]);
            $this->actingAs($user);
            $data = [
                'order_id' => $order->id,
                'type' => 'restaurant',
                'rating' => 4,
                'comment' => "Review {$i}",
                'user_id' => $user->id
            ];
            $this->reviewService->createReview($data);
        }
        
        $reviews = $this->reviewService->getRestaurantReviews($commerce->id);
        $this->assertCount(3, $reviews);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $reviews);
    }
} 