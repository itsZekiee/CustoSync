<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\ElasticsearchService;

class CustomerObserver
{
    public function __construct(
        private ElasticsearchService $elasticsearch
    ) {}

    public function created(Customer $customer): void
    {
        $this->elasticsearch->indexCustomer($customer);
    }

    public function updated(Customer $customer): void
    {
        $this->elasticsearch->indexCustomer($customer);
    }

    public function deleted(Customer $customer): void
    {
        $this->elasticsearch->deleteCustomer($customer->id);
    }
}
