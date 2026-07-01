<?php

namespace App\Repositories;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * Find a pending transaction by gateway reference.
     *
     * @param string $gatewayRef
     * @return Transaction|null
     */
    public function findPendingByGatewayRef(string $gatewayRef): ?Transaction;

    /**
     * Find a transaction by gateway reference.
     *
     * @param string $gatewayRef
     * @return Transaction|null
     */
    public function findByGatewayRef(string $gatewayRef): ?Transaction;

    /**
     * Create a new transaction.
     *
     * @param array $data
     * @return Transaction
     */
    public function createTransaction(array $data): Transaction;

    /**
     * Update an existing transaction.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return bool
     */
    public function updateTransaction(Transaction $transaction, array $data): bool;

    /**
     * Find an active transaction by RFID card UID.
     *
     * @param string $cardUid
     * @return Transaction|null
     */
    public function findActiveByCardUid(string $cardUid): ?Transaction;

    /**
     * Register card UID for a transaction.
     *
     * @param Transaction $transaction
     * @param string $cardUid
     * @return bool
     */
    public function registerCardUid(Transaction $transaction, string $cardUid): bool;

    /**
     * Find latest active transaction for a specific user.
     *
     * @param string $userId
     * @return Transaction|null
     */
    public function findLatestActiveByUserId(string $userId): ?Transaction;
}
