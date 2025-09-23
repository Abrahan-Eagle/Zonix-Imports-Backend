<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Commerce;
use App\Models\Order;
use App\Models\Address;
use App\Models\Phone;
use App\Models\Document;
use App\Models\Notification;
use App\Models\CartItem;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test profile creation with user relationship.
     */
    public function test_profile_can_be_created_with_user(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'id' => $profile->id,
        ]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    /**
     * Test profile roles.
     */
    public function test_profile_roles(): void
    {
        $buyerProfile = Profile::factory()->buyer()->create();
        $sellerProfile = Profile::factory()->seller()->create();
        $adminProfile = Profile::factory()->admin()->create();

        $this->assertEquals('buyer', $buyerProfile->role);
        $this->assertEquals('seller', $sellerProfile->role);
        $this->assertEquals('admin', $adminProfile->role);
    }

    /**
     * Test profile commerce relationship.
     */
    public function test_profile_has_commerce_relationship(): void
    {
        $profile = Profile::factory()->seller()->create();
        $commerce = Commerce::factory()->create(['profile_id' => $profile->id]);

        $this->assertInstanceOf(Commerce::class, $profile->commerce);
        $this->assertEquals($commerce->id, $profile->commerce->id);
    }

    /**
     * Test profile orders relationship.
     */
    public function test_profile_has_orders_relationship(): void
    {
        $profile = Profile::factory()->buyer()->create();
        $orders = Order::factory()->count(3)->create(['profile_id' => $profile->id]);

        $this->assertCount(3, $profile->orders);
        $this->assertTrue($profile->orders->contains($orders->first()));
    }

    /**
     * Test profile addresses relationship.
     */
    public function test_profile_has_addresses_relationship(): void
    {
        $profile = Profile::factory()->create();
        $addresses = Address::factory()->count(2)->create(['profile_id' => $profile->id]);

        $this->assertCount(2, $profile->addresses);
        $this->assertTrue($profile->addresses->contains($addresses->first()));
    }

    /**
     * Test profile phones relationship.
     */
    public function test_profile_has_phones_relationship(): void
    {
        $profile = Profile::factory()->create();
        $phones = Phone::factory()->count(2)->create(['profile_id' => $profile->id]);

        $this->assertCount(2, $profile->phones);
        $this->assertTrue($profile->phones->contains($phones->first()));
    }

    /**
     * Test profile documents relationship.
     */
    public function test_profile_has_documents_relationship(): void
    {
        $profile = Profile::factory()->create();
        $documents = Document::factory()->count(2)->create(['profile_id' => $profile->id]);

        $this->assertCount(2, $profile->documents);
        $this->assertTrue($profile->documents->contains($documents->first()));
    }

    /**
     * Test profile notifications relationship.
     */
    public function test_profile_has_notifications_relationship(): void
    {
        $profile = Profile::factory()->create();
        $notifications = Notification::factory()->count(3)->create(['profile_id' => $profile->id]);

        $this->assertCount(3, $profile->notifications);
        $this->assertTrue($profile->notifications->contains($notifications->first()));
    }

    /**
     * Test profile cart items relationship.
     */
    public function test_profile_has_cart_items_relationship(): void
    {
        $profile = Profile::factory()->buyer()->create();
        $cartItems = CartItem::factory()->count(2)->create(['profile_id' => $profile->id]);

        $this->assertCount(2, $profile->cartItems);
        $this->assertTrue($profile->cartItems->contains($cartItems->first()));
    }

    /**
     * Test profile verification status.
     */
    public function test_profile_verification_status(): void
    {
        $verifiedProfile = Profile::factory()->create(['is_verified' => true]);
        $unverifiedProfile = Profile::factory()->create(['is_verified' => false]);

        $this->assertTrue($verifiedProfile->is_verified);
        $this->assertFalse($unverifiedProfile->is_verified);
    }

    /**
     * Test profile fillable attributes.
     */
    public function test_profile_fillable_attributes(): void
    {
        $profile = Profile::factory()->create([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'role' => 'buyer',
            'is_verified' => true,
            'phone' => '+584121234567',
            'rif' => 'V12345678',
            'bank_account' => '1234567890',
        ]);

        $this->assertEquals('John', $profile->firstName);
        $this->assertEquals('Doe', $profile->lastName);
        $this->assertEquals('buyer', $profile->role);
        $this->assertTrue($profile->is_verified);
        $this->assertEquals('+584121234567', $profile->phone);
        $this->assertEquals('V12345678', $profile->rif);
        $this->assertEquals('1234567890', $profile->bank_account);
    }
}
