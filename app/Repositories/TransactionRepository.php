<?php

namespace App\Repositories;

use App\Models\Transaction;
use MongoDB\BSON\ObjectId;

class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * Find a pending transaction by gateway reference.
     */
    public function findPendingByGatewayRef(string $gatewayRef): ?Transaction
    {
        return Transaction::where('status', 'PENDING')
            ->where('payments.gateway_ref', $gatewayRef)
            ->first();
    }

    /**
     * Find a transaction by gateway reference.
     */
    public function findByGatewayRef(string $gatewayRef): ?Transaction
    {
        return Transaction::where('payments.gateway_ref', $gatewayRef)->first();
    }

    /**
     * Create a new transaction.
     */
    public function createTransaction(array $data): Transaction
    {
        return Transaction::create($data);
    }

    /**
     * Update an existing transaction.
     */
    public function updateTransaction(Transaction $transaction, array $data): bool
    {
        return $transaction->update($data);
    }

    /**
     * Find an active transaction by RFID card UID.
     */
    public function findActiveByCardUid(string $cardUid): ?Transaction
    {
        return Transaction::where('status', 'ACTIVE')
            ->where('card_uid', $cardUid)
            ->first();
    }

    /**
     * Register card UID for a transaction.
     */
    public function registerCardUid(Transaction $transaction, string $cardUid): bool
    {
        return $transaction->update([
            'card_uid' => strtoupper($cardUid)
        ]);
    }

    /**
     * Find latest active transaction for a specific user.
     */
    public function findLatestActiveByUserId(string $userId): ?Transaction
    {
        return Transaction::where('parties.owner_id', new ObjectId($userId))
            ->where('status', 'ACTIVE')
            ->orderBy('timestamps.created_at', 'desc')
            ->first();
    }
}
