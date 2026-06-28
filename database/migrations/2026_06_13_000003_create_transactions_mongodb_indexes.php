<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->table('transactions', function (Blueprint $collection) {
            // Indeks komposit pelacakan riwayat transaksi user berdasarkan statusnya
            $collection->index([
                'parties.owner_id' => 1,
                'status' => 1,
            ], 'user_transactions_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->table('transactions', function (Blueprint $collection) {
            $collection->dropIndex('user_transactions_status_index');
        });
    }
};
