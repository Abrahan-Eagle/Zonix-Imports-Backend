<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\PostLike;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Profile;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $postService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = new PostService();
    }

    public function test_get_posts_with_search_filter()
    {
        // Crear posts de prueba
        Post::factory()->create(['name' => 'Pizza Margherita', 'price' => 15.00]);
        Post::factory()->create(['name' => 'Hamburguesa', 'price' => 12.00]);
        Post::factory()->create(['name' => 'Pizza Hawaiana', 'price' => 18.00]);

        $filters = ['search' => 'Pizza'];
        $posts = $this->postService->getPostsWithFilters($filters);

        $this->assertCount(2, $posts);
        $this->assertTrue($posts->every(function ($post) {
            return str_contains(strtolower($post->name), 'pizza');
        }));
    }

    public function test_get_posts_with_price_filter()
    {
        Post::factory()->create(['name' => 'Producto Barato', 'price' => 5.00]);
        Post::factory()->create(['name' => 'Producto Medio', 'price' => 15.00]);
        Post::factory()->create(['name' => 'Producto Caro', 'price' => 25.00]);

        $filters = ['min_price' => 10, 'max_price' => 20];
        $posts = $this->postService->getPostsWithFilters($filters);

        $this->assertCount(1, $posts);
        $this->assertEquals('Producto Medio', $posts->first()->name);
    }

    public function test_toggle_favorite()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->create();

        // Agregar a favoritos
        $result = $this->postService->toggleFavorite($post->id, $user->id);
        $this->assertTrue($result['is_favorite']);
        $this->assertEquals('Agregado a favoritos', $result['message']);

        // Remover de favoritos
        $result = $this->postService->toggleFavorite($post->id, $user->id);
        $this->assertFalse($result['is_favorite']);
        $this->assertEquals('Removido de favoritos', $result['message']);
    }

    public function test_get_user_favorites()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();
        $post3 = Post::factory()->create();

        // Agregar posts a favoritos
        PostLike::create(['post_id' => $post1->id, 'user_id' => $user->id, 'profile_id' => $profile->id]);
        PostLike::create(['post_id' => $post2->id, 'user_id' => $user->id, 'profile_id' => $profile->id]);

        $favorites = $this->postService->getUserFavorites($user->id);

        $this->assertCount(2, $favorites);
        $this->assertTrue($favorites->contains($post1));
        $this->assertTrue($favorites->contains($post2));
        $this->assertFalse($favorites->contains($post3));
    }
} 