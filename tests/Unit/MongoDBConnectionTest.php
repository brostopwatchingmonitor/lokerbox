<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\LockerStation;
use App\Models\Transaction;

class MongoDBConnectionTest extends TestCase
{
    /**
     * Uji apakah model-model menggunakan MongoDB connection dan driver yang benar.
     */
    public function test_models_use_mongodb_connection(): void
    {
        $user = new User();
        $this->assertEquals('mongodb', $user->getConnectionName());
        $this->assertEquals('users', $user->getTable());

        $station = new LockerStation();
        $this->assertEquals('mongodb', $station->getConnectionName());
        $this->assertEquals('locker_stations', $station->getTable());

        $transaction = new Transaction();
        $this->assertEquals('mongodb', $transaction->getConnectionName());
        $this->assertEquals('transactions', $transaction->getTable());
    }
}
