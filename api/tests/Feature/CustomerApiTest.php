<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\ElasticsearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ES service so tests don't require a running Elasticsearch instance
        $this->mock(ElasticsearchService::class, function ($mock) {
            $mock->shouldReceive('indexCustomer')->andReturnNull();
            $mock->shouldReceive('deleteCustomer')->andReturnNull();
            $mock->shouldReceive('search')->andReturn([]);
        });
    }

    public function test_can_create_customer(): void
    {
        $response = $this->postJson('/api/customers', [
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.first_name', 'Jane');

        $this->assertDatabaseHas('customers', ['email' => 'jane@example.com']);
    }

    public function test_create_customer_validates_required_fields(): void
    {
        $response = $this->postJson('/api/customers', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
    }

    public function test_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/customers');

        $response->assertStatus(200);
    }

    public function test_can_show_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $customer->id);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'first_name' => 'Updated',
            'last_name'  => $customer->last_name,
            'email'      => $customer->email,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.first_name', 'Updated');
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_email_must_be_unique(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/customers', [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'taken@example.com',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }
}
