<?php

namespace Tests\Feature;

use App\Models\Bank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_banks()
    {
        Bank::factory()->create(['name' => 'Banco de Venezuela', 'is_active' => true]);
        Bank::factory()->create(['name' => 'Banesco', 'is_active' => true]);
        Bank::factory()->create(['name' => 'Banco Inactivo', 'is_active' => false]);

        $response = $this->getJson('/api/banks');
        $response->assertStatus(200)->assertJson(['success' => true]);
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Banco de Venezuela', $data[0]['name']);
        $this->assertEquals('Banesco', $data[1]['name']);
    }
} 