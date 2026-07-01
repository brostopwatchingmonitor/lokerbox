<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService implements MidtransServiceInterface
{
    protected string $serverKey;
    protected string $clientKey;
    protected bool $isProduction;
    protected string $baseUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key', '');
        $this->clientKey = config('services.midtrans.client_key', '');
        $this->isProduction = filter_var(config('services.midtrans.is_production', false), FILTER_VALIDATE_BOOLEAN);
        
        // Base API endpoint determination based on environment mode
        $this->baseUrl = $this->isProduction
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    /**
     * Check if running in mock/demo mode (server key empty or default).
     */
    public function isMockMode(): bool
    {
        return empty($this->serverKey) || $this->serverKey === 'YOUR_MIDTRANS_SERVER_KEY';
    }

    /**
     * Create Midtrans Snap Transaction token.
     */
    public function createSnapTransaction(string $orderId, float $amount, array $customerDetails, array $itemDetails): array
    {
        if ($this->isMockMode()) {
            Log::info('Midtrans Service: Running in MOCK Mode for Order ID: ' . $orderId);
            return [
                'success' => true,
                'token' => 'mock-snap-token-' . uniqid(),
                'orderId' => $orderId,
                'isMock' => true,
            ];
        }

        try {
            $authHeader = base64_encode($this->serverKey . ':');
            $url = $this->baseUrl . '/snap/v1/transactions';

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $authHeader,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'customer_details' => $customerDetails,
                'item_details' => $itemDetails,
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['token'])) {
                return [
                    'success' => true,
                    'token' => $result['token'],
                    'orderId' => $orderId,
                    'isMock' => false,
                ];
            }

            Log::error('Midtrans Service - API Error response: ' . json_encode($result));
            return [
                'success' => false,
                'error' => $result['error_messages'][0] ?? 'Gagal membuat transaksi di gateway pembayaran.',
            ];
        } catch (\Exception $e) {
            Log::error('Midtrans Service - Connection Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Kesalahan koneksi ke Midtrans: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Midtrans callback signature.
     */
    public function verifySignature(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            return false;
        }

        if ($this->isMockMode()) {
            Log::info("Midtrans Service: Mock signature validated as true.");
            return true;
        }

        $localSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        return hash_equals($localSignature, $signatureKey);
    }

    /**
     * Get transaction status from Midtrans.
     */
    public function getTransactionStatus(string $orderId): array
    {
        if ($this->isMockMode()) {
            return [
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'status_code' => '200',
                'gross_amount' => '0',
            ];
        }

        try {
            $authHeader = base64_encode($this->serverKey . ':');
            $apiUrl = $this->isProduction
                ? "https://api.midtrans.com/v2/{$orderId}/status"
                : "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

            $response = Http::withHeaders(['Authorization' => 'Basic ' . $authHeader])->get($apiUrl);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error("Midtrans Service: Failed to fetch transaction status for {$orderId}: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error("Midtrans Service: Status check connection failed for {$orderId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Map Midtrans transaction status to application statuses.
     */
    public function mapTransactionStatus(string $transactionStatus, ?string $fraudStatus = null, ?string $paymentType = null): array
    {
        $paymentStatus = 'PENDING';
        $lockerStatus = 'PENDING';
        $isPaid = false;

        if ($transactionStatus === 'capture') {
            if ($paymentType === 'credit_card') {
                if ($fraudStatus === 'challenge') {
                    $paymentStatus = 'PENDING';
                    $lockerStatus = 'PENDING';
                } else {
                    $paymentStatus = 'SUCCESS';
                    $lockerStatus = 'ACTIVE';
                    $isPaid = true;
                }
            } else {
                $paymentStatus = 'SUCCESS';
                $lockerStatus = 'ACTIVE';
                $isPaid = true;
            }
        } elseif ($transactionStatus === 'settlement') {
            $paymentStatus = 'SUCCESS';
            $lockerStatus = 'ACTIVE';
            $isPaid = true;
        } elseif ($transactionStatus === 'pending') {
            $paymentStatus = 'PENDING';
            $lockerStatus = 'PENDING';
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $paymentStatus = 'FAILED';
            $lockerStatus = 'CANCELLED';
        }

        return [
            'payment_status' => $paymentStatus,
            'locker_status' => $lockerStatus,
            'is_paid' => $isPaid,
        ];
    }
}
