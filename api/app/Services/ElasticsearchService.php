<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    private string $baseUrl;
    private string $index = 'customers';

    public function __construct()
    {
        $this->baseUrl = config('services.elasticsearch.host', 'http://searcher:9200');
    }

    /**
     * Index (create or update) a customer document in Elasticsearch.
     */
    public function indexCustomer(Customer $customer): void
    {
        $url = "{$this->baseUrl}/{$this->index}/_doc/{$customer->id}";

        $response = Http::put($url, $customer->toSearchArray());

        if ($response->failed()) {
            Log::error('ES index failed', [
                'customer_id' => $customer->id,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);
        }
    }

    /**
     * Delete a customer document from Elasticsearch.
     */
    public function deleteCustomer(int $customerId): void
    {
        $url = "{$this->baseUrl}/{$this->index}/_doc/{$customerId}";

        $response = Http::delete($url);

        if ($response->failed()) {
            Log::error('ES delete failed', [
                'customer_id' => $customerId,
                'status'      => $response->status(),
                'body'        => $response->body(),
            ]);
        }
    }

    /**
     * Search customers by query string across name and email fields.
     *
     * @return array<int> Customer IDs matching the query.
     */
    public function search(string $query): array
    {
        $url = "{$this->baseUrl}/{$this->index}/_search";

        $response = Http::post($url, [
            'query' => [
                'multi_match' => [
                    'query'  => $query,
                    'fields' => ['first_name', 'last_name', 'email'],
                    'type'   => 'phrase_prefix',
                ],
            ],
            'size' => 100,
        ]);

        if ($response->failed()) {
            Log::error('ES search failed', ['query' => $query, 'body' => $response->body()]);
            return [];
        }

        $hits = $response->json('hits.hits', []);

        return array_map(fn ($hit) => (int) $hit['_source']['id'], $hits);
    }

    /**
     * Create the customers index with mappings.
     */
    public function createIndex(): void
    {
        $url = "{$this->baseUrl}/{$this->index}";

        Http::put($url, [
            'mappings' => [
                'properties' => [
                    'id'             => ['type' => 'integer'],
                    'first_name'     => ['type' => 'text'],
                    'last_name'      => ['type' => 'text'],
                    'email'          => ['type' => 'text'],
                    'contact_number' => ['type' => 'keyword'],
                ],
            ],
        ]);
    }
}
