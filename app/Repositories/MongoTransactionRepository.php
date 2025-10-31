<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Small repository that writes a copy of transactions into the MongoDB
 * connection defined as `mongodb` in `config/database.php`.
 */
class MongoTransactionRepository
{
    protected string $connection = 'mongodb';
    protected string $collection = 'transactions';

    /**
     * Insert a payload into the MongoDB collection.
     * Uses the jenssegers/mongodb DB connection API (DB::connection('mongodb')->collection(...)).
     *
     * @param array $payload
     * @return mixed Insert result (driver-dependent)
     */
    public function insert(array $payload)
    {
        // Convert DateTimeInterface values to ISO strings (Carbon implements DateTimeInterface)
        foreach (['created_at', 'updated_at'] as $ts) {
            if (isset($payload[$ts])) {
                if ($payload[$ts] instanceof \DateTimeInterface) {
                    $payload[$ts] = $payload[$ts]->format(\DateTime::ATOM);
                } elseif (is_object($payload[$ts]) && method_exists($payload[$ts], 'toAtomString')) {
                    $payload[$ts] = $payload[$ts]->toAtomString();
                }
            }
        }

        // Use the query builder `table()` which is provided by the jenssegers connection.
        // It will perform the insert on the configured MongoDB connection.
        return DB::connection($this->connection)->table($this->collection)->insert($payload);
    }
}
