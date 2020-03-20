<?php

declare(strict_types=1);

namespace App\Service;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Core\Iterator\ItemIterator;

class BigQueryService
{
    /** @var BigQueryClient */
    private $bigQuery;

    public function __construct()
    {
        $this->bigQuery = new BigQueryClient();
    }

    public function getClient(): BigQueryClient
    {
        return $this->bigQuery;
    }
}