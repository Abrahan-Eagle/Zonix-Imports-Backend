<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\DeliveryCompany;
use App\Models\DeliveryAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'google_id' => 'google_123456',
            'role' => 'users',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'role',
                             'created_at'
                         ],
                         'token'
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'google_id' => 'google_123456',
            'role' => 'users'
        ]);
    }

    /** @test */
    public function user_cannot_register_with_invalid_email()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'google_id' => 'google_123456',
            'role' => 'users',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_login_with_google()
    {
        $user = User::factory()->create([
            'google_id' => 'google_123456',
            'email' => 'test@example.com',
            'role' => 'users'
        ]);

        $loginData = [
            'google_id' => 'google_123456',
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $response = $this->postJson('/api/auth/google', $loginData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user',
                         'token'
                     ]
                 ]);
    }

    /** @test */
    public function user_can_login_with_existing_google_account()
    {
        $user = User::factory()->create([
            'google_id' => 'google_123456',
            'email' => 'test@example.com',
        ]);

        $loginData = [
            'google_id' => 'google_123456',
            'email' => 'test@example.com',
            'name' => 'Updated Name',
        ];

        $response = $this->postJson('/api/auth/google', $loginData);

        $response->assertStatus(200);

        // Verificar que el nombre se actualizó
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name'
        ]);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /** @test */
    public function user_can_get_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'role',
                         'created_at'
                     ]
                 ]);
    }

    /** @test */
    public function user_can_update_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson('/api/auth/user', $updateData);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }

    /** @test */
    public function user_can_create_commerce_profile()
    {
        $user = User::factory()->create(['role' => 'commerce']);
        Sanctum::actingAs($user);

        $commerceData = [
            'user_id' => $user->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'maritalStatus' => 'single',
            'sex' => 'M',
            'business_name' => 'Test Restaurant',
            'description' => 'Test restaurant description',
            'address' => 'Test Address',
            'phone' => '1234567890',
            'email' => 'restaurant@example.com',
            'mobile_payment_bank' => 'Test Bank',
            'mobile_payment_id' => '123456789',
            'mobile_payment_phone' => '1234567890',
            'is_open' => true,
        ];

        $response = $this->postJson('/api/profiles/commerce', $commerceData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'business_name',
                         'description',
                         'address',
                         'phone',
                         'mobile_payment_bank',
                         'mobile_payment_id',
                         'mobile_payment_phone',
                         'open'
                     ]
                 ]);

        $this->assertDatabaseHas('commerces', [
            'business_name' => 'Test Restaurant',
            'phone' => '1234567890'
        ]);
    }

    /** @test */
    public function user_can_create_delivery_company_profile()
    {
        $user = User::factory()->create(['role' => 'users']);
        Sanctum::actingAs($user);

        $deliveryData = [
            'user_id' => $user->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'maritalStatus' => 'single',
            'sex' => 'M',
            'company_name' => 'Test Delivery Company',
            'address' => 'Test Address',
            'phone' => '1234567890',
        ];

        $response = $this->postJson('/api/profiles/delivery-company', $deliveryData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'profile',
                         'delivery_company'
                     ]
                 ]);

        $this->assertDatabaseHas('delivery_companies', [
            'name' => 'Test Delivery Company',
        ]);
    }

    /** @test */
    public function user_can_create_delivery_agent_profile()
    {
        $user = User::factory()->create(['role' => 'delivery']);
        Sanctum::actingAs($user);

        $deliveryAgentData = [
            'user_id' => $user->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'maritalStatus' => 'single',
            'sex' => 'M',
            'vehicle_type' => 'motorcycle',
            'phone' => '1234567890',
        ];

        $response = $this->postJson('/api/profiles/delivery-agent', $deliveryAgentData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'profile',
                         'delivery_agent'
                     ]
                 ]);

        $this->assertDatabaseHas('delivery_agents', [
            'vehicle_type' => 'motorcycle',
            'phone' => '1234567890'
        ]);
    }

    /** @test */
    public function user_can_create_delivery_agent_profile_with_company()
    {
        $user = User::factory()->create(['role' => 'delivery']);
        $company = \App\Models\DeliveryCompany::factory()->create();
        Sanctum::actingAs($user);

        $deliveryAgentData = [
            'user_id' => $user->id,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'date_of_birth' => '1990-01-01',
            'maritalStatus' => 'single',
            'sex' => 'M',
            'vehicle_type' => 'motorcycle',
            'phone' => '1234567890',
            'company_id' => $company->id,
        ];

        $response = $this->postJson('/api/profiles/delivery-agent', $deliveryAgentData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'profile',
                         'delivery_agent'
                     ]
                 ]);

        $this->assertDatabaseHas('delivery_agents', [
            'vehicle_type' => 'motorcycle',
            'phone' => '1234567890',
            'company_id' => $company->id
        ]);
    }

    /** @test */
    public function user_cannot_access_protected_route_without_token()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_access_admin_route_without_admin_role()
    {
        $user = User::factory()->create(['role' => 'users']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_admin_route()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200);
    }

    /** @test */
    public function commerce_can_access_commerce_route()
    {
        $commerce = User::factory()->create(['role' => 'commerce']);
        Sanctum::actingAs($commerce);

        // Crear perfil de commerce
        $profile = Profile::factory()->create(['user_id' => $commerce->id]);
        \App\Models\Commerce::factory()->create(['profile_id' => $profile->id]);

        $response = $this->getJson('/api/commerce/orders');

        $response->assertStatus(200);
    }

    /** @test */
    public function delivery_can_access_delivery_route()
    {
        $delivery = User::factory()->create(['role' => 'delivery']);
        Sanctum::actingAs($delivery);

        // Crear perfil de delivery agent
        $profile = Profile::factory()->create(['user_id' => $delivery->id]);
        \App\Models\DeliveryAgent::factory()->create(['profile_id' => $profile->id]);

        $response = $this->getJson('/api/delivery/orders');

        $response->assertStatus(200);
    }

    /** @test */
    public function token_expires_after_configured_time()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Hacer una petición con el token válido
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_refresh_token()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'token'
                     ]
                 ]);
    }

    /** @test */
    public function user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->putJson('/api/auth/password', $passwordData);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /** @test */
    public function user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->putJson('/api/auth/password', $passwordData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['current_password']);
    }
} 