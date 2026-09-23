<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ElasticsearchServiceTest extends TestCase
{
    public function test_index_customer_sends_put_to_elasticsearch(): void
    {
        Http::fake(['*' => Http::response(['result' => 'created'], 201)]);

        $customer = new Customer([
            'first_name'     => 'Jane',
            'last_name'      => 'Doe',
            'email'          => 'jane@example.com',
            'contact_number' => '123',
        ]);
        $customer->id = 1;

        $service = new ElasticsearchService();
        $service->indexCustomer($customer);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/customers/_doc/1')
                && $request->method() === 'PUT';
        });
    }

    public function test_search_returns_customer_ids(): void
    {
        Http::fake([
            '*/_search' => Http::response([
                'hits' => [
                    'hits' => [
                        ['_source' => ['id' => 1]],
                        ['_source' => ['id' => 5]],
                    ],
                ],
            ]),
        ]);

        $service = new ElasticsearchService();
        $ids = $service->search('jane');

        $this->assertEquals([1, 5], $ids);
    }

    public function test_search_returns_empty_on_failure(): void
    {
        Http::fake(['*/_search' => Http::response([], 500)]);

        $service = new ElasticsearchService();
        $ids = $service->search('jane');

        $this->assertEmpty($ids);
    }
}
