<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\ElasticsearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private ElasticsearchService $elasticsearch
    ) {}

    /**
     * List customers. If ?search= is provided, query Elasticsearch first.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');

        if ($search) {
            $ids = $this->elasticsearch->search($search);

            if (empty($ids)) {
                return response()->json(['data' => []]);
            }

            $customers = Customer::whereIn('id', $ids)->get();
        } else {
            $customers = Customer::paginate(20);
            return response()->json($customers);
        }

        return response()->json(['data' => $customers]);
    }

    /**
     * Show a single customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json(['data' => $customer]);
    }

    /**
     * Create a new customer.
     */
    public function store(CustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return response()->json(['data' => $customer], 201);
    }

    /**
     * Update an existing customer.
     */
    public function update(CustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return response()->json(['data' => $customer]);
    }

    /**
     * Delete a customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
