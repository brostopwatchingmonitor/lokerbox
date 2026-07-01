<?php

namespace App\Services\Payment;

interface MidtransServiceInterface
{
    /**
     * Create Midtrans Snap Transaction token.
     *
     * @param string $orderId
     * @param float $amount
     * @param array $customerDetails
     * @param array $itemDetails
     * @return array ['success' => bool, 'token' => ?string, 'isMock' => bool, 'error' => ?string]
     */
    public function createSnapTransaction(string $orderId, float $amount, array $customerDetails, array $itemDetails): array;

    /**
     * Verify Midtrans callback signature.
     *
     * @param array $payload
     * @return bool
     */
    public function verifySignature(array $payload): bool;

    /**
     * Get transaction status from Midtrans.
     *
     * @param string $orderId
     * @return array
     */
    public function getTransactionStatus(string $orderId): array;

    /**
     * Check if running in mock/demo mode (server key empty or default).
     *
     * @return bool
     */
    public function isMockMode(): bool;

    /**
     * Map Midtrans transaction status to application statuses.
     *
     * @param string $transactionStatus
     * @param string|null $fraudStatus
     * @param string|null $paymentType
     * @return array ['payment_status' => string, 'locker_status' => string, 'is_paid' => bool]
     */
    public function mapTransactionStatus(string $transactionStatus, ?string $fraudStatus = null, ?string $paymentType = null): array;
}
