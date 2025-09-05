<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\DeliveryAgent;
use App\Models\DeliveryCompany;
use Laravel\Sanctum\Sanctum;

class RoleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tests de autenticación para cada rol
     */
    public function test_users_can_login_with_valid_credentials()
    {
        $user = User::factory()->buyer()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'role'
                        ],
                        'token'
                    ]
                ]);

        $this->assertEquals('users', $response->json('data.user.role'));
    }

    public function test_commerce_can_login_with_valid_credentials()
    {
        $commerceUser = User::factory()->commerce()->create([
            'email' => 'commerce@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'commerce@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('commerce', $response->json('data.user.role'));
    }

    public function test_delivery_agent_can_login_with_valid_credentials()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create([
            'email' => 'delivery@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'delivery@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('delivery', $response->json('data.user.role'));
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('admin', $response->json('data.user.role'));
    }

    public function test_delivery_company_can_login_with_valid_credentials()
    {
        $deliveryCompanyUser = User::factory()->deliveryCompany()->create([
            'email' => 'deliverycompany@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'deliverycompany@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('delivery_company', $response->json('data.user.role'));
    }

    /**
     * Tests de autenticación fallida
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->buyer()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_with_nonexistent_user()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Tests de logout
     */
    public function test_users_can_logout()
    {
        $user = User::factory()->buyer()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_commerce_can_logout()
    {
        $commerceUser = User::factory()->commerce()->create();
        Sanctum::actingAs($commerceUser);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    public function test_delivery_agent_can_logout()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create();
        Sanctum::actingAs($deliveryUser);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    /**
     * Tests de verificación de token
     */
    public function test_token_verification_for_users()
    {
        $user = User::factory()->buyer()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => 'users'
                    ]
                ]);
    }

    public function test_token_verification_for_commerce()
    {
        $commerceUser = User::factory()->commerce()->create();
        Sanctum::actingAs($commerceUser);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $commerceUser->id,
                        'email' => $commerceUser->email,
                        'role' => 'commerce'
                    ]
                ]);
    }

    public function test_token_verification_for_delivery_agent()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create();
        Sanctum::actingAs($deliveryUser);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $deliveryUser->id,
                        'email' => $deliveryUser->email,
                        'role' => 'delivery'
                    ]
                ]);
    }

    /**
     * Tests de autorización por rol
     */
    public function test_users_can_only_access_their_own_data()
    {
        $user1 = User::factory()->buyer()->create();
        $user2 = User::factory()->buyer()->create();
        $profile1 = Profile::factory()->create(['user_id' => $user1->id]);
        $profile2 = Profile::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        // Puede acceder a su propio perfil
        $response = $this->getJson("/api/buyer/profiles/{$profile1->id}");
        $response->assertStatus(200);

        // No puede acceder al perfil de otro usuario
        $response = $this->getJson("/api/buyer/profiles/{$profile2->id}");
        $response->assertStatus(403);
    }

    public function test_commerce_can_only_access_their_own_orders()
    {
        $commerceUser1 = User::factory()->commerce()->create();
        $commerceUser2 = User::factory()->commerce()->create();
        $profile1 = Profile::factory()->create(['user_id' => $commerceUser1->id]);
        $profile2 = Profile::factory()->create(['user_id' => $commerceUser2->id]);
        $commerce1 = Commerce::factory()->create(['profile_id' => $profile1->id]);
        $commerce2 = Commerce::factory()->create(['profile_id' => $profile2->id]);

        // Refrescar las relaciones
        $commerceUser1->refresh();
        $commerceUser2->refresh();

        Sanctum::actingAs($commerceUser1);

        // Puede acceder a sus propias órdenes
        $response = $this->getJson('/api/commerce/orders');
        $response->assertStatus(200);

        // No puede acceder a órdenes de otro comercio
        $response = $this->getJson("/api/commerce/orders?commerce_id={$commerce2->id}");
        $response->assertStatus(403);
    }

    public function test_delivery_agent_can_only_access_assigned_orders()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create();
        $profile = Profile::factory()->create(['user_id' => $deliveryUser->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $profile->id]);

        Sanctum::actingAs($deliveryUser);

        // Solo puede ver órdenes asignadas a él
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(200);
    }

    /**
     * Tests de middleware de roles
     */
    public function test_admin_middleware_allows_only_admin_users()
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->buyer()->create();

        // Admin puede acceder
        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(200);

        // Usuario normal no puede acceder
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(403);
    }

    public function test_commerce_middleware_allows_only_commerce_users()
    {
        $commerceUser = User::factory()->commerce()->create();
        $profile = Profile::factory()->create(['user_id' => $commerceUser->id]);
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);
        $user = User::factory()->buyer()->create();

        // Refrescar las relaciones
        $commerceUser->refresh();

        // Commerce puede acceder
        Sanctum::actingAs($commerceUser);
        $response = $this->getJson('/api/commerce/orders');
        $response->assertStatus(200);

        // Usuario normal no puede acceder
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/commerce/orders');
        $response->assertStatus(403);
    }

    public function test_delivery_middleware_allows_only_delivery_users()
    {
        $deliveryUser = User::factory()->deliveryAgent()->create();
        $profile = Profile::factory()->create(['user_id' => $deliveryUser->id]);
        $deliveryAgent = DeliveryAgent::factory()->create(['profile_id' => $profile->id]);
        $user = User::factory()->buyer()->create();

        // Refrescar las relaciones
        $deliveryUser->refresh();

        // Delivery puede acceder
        Sanctum::actingAs($deliveryUser);
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(200);

        // Usuario normal no puede acceder
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/delivery/orders');
        $response->assertStatus(403);
    }

    /**
     * Tests de registro por rol
     */
    public function test_users_can_register_with_buyer_role()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'users'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'name' => 'Test User',
                            'email' => 'test@example.com',
                            'role' => 'users'
                        ]
                    ]
                ]);
    }

    public function test_commerce_can_register_with_commerce_role()
    {
        $commerceData = [
            'name' => 'Test Commerce',
            'email' => 'commerce@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'commerce'
        ];

        $response = $this->postJson('/api/auth/register', $commerceData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'name' => 'Test Commerce',
                            'email' => 'commerce@example.com',
                            'role' => 'commerce'
                        ]
                    ]
                ]);
    }

    public function test_delivery_agent_can_register_with_delivery_role()
    {
        $deliveryData = [
            'name' => 'Test Delivery',
            'email' => 'delivery@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'delivery'
        ];

        $response = $this->postJson('/api/auth/register', $deliveryData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'user' => [
                            'name' => 'Test Delivery',
                            'email' => 'delivery@example.com',
                            'role' => 'delivery'
                        ]
                    ]
                ]);
    }

    /**
     * Tests de validación de roles
     */
    public function test_registration_fails_with_invalid_role()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'invalid_role'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422);
    }

    public function test_registration_requires_role_field()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
            // Sin campo role
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422);
    }
} 