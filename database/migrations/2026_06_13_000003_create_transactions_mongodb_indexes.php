<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::connection($this->connection)->table('transactions', function (Blueprint $collection) {
            // Indeks komposit pelacakan riwayat transaksi user berdasarkan statusnya
            $collection->index([
                'parties.owner_id' => 1,
                'status' => 1,
            ], 'user_transactions_status_index');

            // Indeks pencarian transaksi berdasarkan kode referensi pembayaran gateway
            $collection->index('payments.gateway_ref');

            // Indeks pencarian RFID card UID aktif untuk akses loker
            $collection->index('card_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->table('transactions', function (Blueprint $collection) {
            $collection->dropIndex('user_transactions_status_index');
            $collection->dropIndex(['payments.gateway_ref']);
            $collection->dropIndex(['card_uid']);
        });
    }
};
