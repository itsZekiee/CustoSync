<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class CreateElasticsearchIndex extends Command
{
    protected $signature = 'es:create-index';
    protected $description = 'Create the customers Elasticsearch index with mappings';

    public function handle(ElasticsearchService $elasticsearch): int
    {
        $elasticsearch->createIndex();
        $this->info('Elasticsearch "customers" index created.');

        return self::SUCCESS;
    }
}
