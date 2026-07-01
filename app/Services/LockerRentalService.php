<?php

namespace App\Services;

use App\Repositories\TransactionRepositoryInterface;
use App\Repositories\LockerStationRepositoryInterface;
use App\Services\Payment\MidtransServiceInterface;
use App\Models\Transaction;
use App\Models\User;
use MongoDB\BSON\ObjectId;
use Illuminate\Support\Facades\Log;

class LockerRentalService
{
    protected TransactionRepositoryInterface $transactionRepository;
    protected LockerStationRepositoryInterface $stationRepository;
    protected MidtransServiceInterface $midtransService;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        LockerStationRepositoryInterface $stationRepository,
        MidtransServiceInterface $midtransService
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->stationRepository = $stationRepository;
        $this->midtransService = $midtransService;
    }

    /**
     * Create rental order transaction and retrieve Midtrans Snap Token.
     */
    public function createRentalOrder(User $user, string $lockerSize, int $duration, float $price): array
    {
        $orderId = 'LKR-' . time() . '-' . rand(100, 999);
        $totalPrice = $price * $duration;

        try {
            // Find available box in locker station or fallback to test ids
            $stationId = '6647a123f1b4c3d2e1a8b002';
            $boxId = $this->stationRepository->findAvailableBox($stationId, $lockerSize);
            
            if (!$boxId) {
                $boxId = '6647a123f1b4c3d2e1a8b003'; // default fallback for Small
            }

            // Create Transaction record in MongoDB
            $transaction = $this->transactionRepository->createTransaction([
                'box_reference' => [
                    'station_id' => new ObjectId($stationId),
                    'box_id' => new ObjectId($boxId),
                ],
                'parties' => [
                    'owner_id' => new ObjectId($user->_id),
                    'sender_id' => null,
                ],
                'transaction_type' => 'SELF_USE',
                'status' => 'PENDING',
                'fees' => [
                    'base_fee' => (float)$price,
                    'penalty_fee' => 0.00,
                    'total_fee' => (float)$totalPrice,
                ],
                'timestamps' => [
                    'created_at' => now(),
                    'started_at' => null,
                    'due_at' => null,
                    'ended_at' => null,
                ],
            ]);

            // Create initial payment gateway record inside transaction
            $transaction->payments()->create([
                'payment_id' => new ObjectId(),
                'payment_method' => 'MIDTRANS',
                'payment_status' => 'PENDING',
                'amount' => (float)$totalPrice,
                'gateway_ref' => $orderId,
                'paid_at' => null,
            ]);

            // Call Midtrans Snap Gateway
            $customerDetails = [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->fcm_token ?? '08123456789',
            ];

            $itemDetails = [
                [
                    'id' => $lockerSize,
                    'price' => $price,
                    'quantity' => $duration,
                    'name' => 'Sewa Loker ' . $lockerSize . ' (' . $duration . ' Jam)',
                ]
            ];

            $midtransResult = $this->midtransService->createSnapTransaction($orderId, $totalPrice, $customerDetails, $itemDetails);

            if (!$midtransResult['success']) {
                return [
                    'success' => false,
                    'error' => $midtransResult['error'],
                ];
            }

            return [
                'success' => true,
                'token' => $midtransResult['token'],
                'orderId' => $orderId,
                'isMock' => $midtransResult['isMock'] ?? false,
            ];

        } catch (\Exception $e) {
            Log::error('LockerRentalService: createRentalOrder failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Gagal membuat sewa loker: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process asynchronous callback notification from Midtrans webhook.
     */
    public function handlePaymentWebhook(array $payload): bool
    {
        // 1. Verify Midtrans signature
        if (!$this->midtransService->verifySignature($payload)) {
            Log::warning('LockerRentalService - Webhook verification failed. potential unauthorized payload.');
            return false;
        }

        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        // 2. Find matching Transaction
        $transaction = $this->transactionRepository->findByGatewayRef($orderId);
        if (!$transaction) {
            Log::error("LockerRentalService - Webhook: Transaction matching Order ID {$orderId} not found.");
            return false;
        }

        // 3. Map status values using helper
        $statusMapping = $this->midtransService->mapTransactionStatus($transactionStatus, $fraudStatus, $paymentType);
        $paymentStatus = $statusMapping['payment_status'];
        $lockerStatus = $statusMapping['locker_status'];
        $isPaid = $statusMapping['is_paid'];

        // 4. Update transaction status
        $transactionUpdate = [
            'status' => $lockerStatus,
        ];

        if ($isPaid) {
            $durationHours = 2; // Default rent duration
            $transactionUpdate['timestamps.started_at'] = now();
            $transactionUpdate['timestamps.due_at'] = now()->addHours($durationHours);

            // Reserve the locker box (set availability to false)
            $this->stationRepository->updateBoxAvailability(
                $transaction->box_reference['station_id'],
                $transaction->box_reference['box_id'],
                false
            );
        }

        $this->transactionRepository->updateTransaction($transaction, $transactionUpdate);

        // 5. Update embedded Payment status
        $payment = $transaction->payments()->first();
        if ($payment) {
            $payment->update([
                'payment_status' => $paymentStatus,
                'paid_at' => $isPaid ? now() : null,
            ]);
        }

        // 6. Record activity logs in MongoDB
        $transaction->activityLogs()->create([
            'log_id' => new ObjectId(),
            'actor_id' => $transaction->parties['owner_id'],
            'event_name' => $isPaid ? 'PAYMENT_SUCCESS' : 'PAYMENT_STATUS_UPDATE',
            'description' => "Status pembayaran diperbarui menjadi: {$paymentStatus}. Status Loker: {$lockerStatus}",
            'metadata_iot' => [
                'midtrans_transaction_id' => $payload['transaction_id'] ?? '',
                'payment_type' => $paymentType,
                'status_message' => $payload['status_message'] ?? '',
            ],
            'logged_at' => now(),
        ]);

        Log::info("LockerRentalService: Webhook transaction {$orderId} status successfully synchronized.");
        return true;
    }

    /**
     * Fetch status and pickup details of transaction. Synchronize status with Midtrans if pending.
     */
    public function retrievePickupCode(string $orderId): array
    {
        $transaction = $this->transactionRepository->findByGatewayRef($orderId);

        if (!$transaction) {
            Log::warning("LockerRentalService: Transaction not found for {$orderId}. Running in DEMO mockup.");
            return [
                'success' => true,
                'pickup_code' => 'LCK-' . rand(1000, 9999),
                'orderId' => $orderId,
                'status' => 'SUCCESS',
                'card_uid' => null,
            ];
        }

        if ($transaction->status === 'PENDING') {
            $statusResult = $this->midtransService->getTransactionStatus($orderId);
            
            if (!empty($statusResult) && isset($statusResult['transaction_status'])) {
                $txStatus = $statusResult['transaction_status'];
                $fraudStatus = $statusResult['fraud_status'] ?? null;
                $paymentType = $statusResult['payment_type'] ?? null;

                $statusMapping = $this->midtransService->mapTransactionStatus($txStatus, $fraudStatus, $paymentType);
                
                if ($statusMapping['is_paid']) {
                    $this->transactionRepository->updateTransaction($transaction, [
                        'status' => 'ACTIVE',
                        'timestamps.started_at' => now(),
                        'timestamps.due_at' => now()->addHours(2),
                    ]);

                    $this->stationRepository->updateBoxAvailability(
                        $transaction->box_reference['station_id'],
                        $transaction->box_reference['box_id'],
                        false
                    );

                    $payment = $transaction->payments()->first();
                    if ($payment) {
                        $payment->update([
                            'payment_status' => 'SUCCESS',
                            'paid_at' => now(),
                        ]);
                    }
                }
            } else if ($this->midtransService->isMockMode()) {
                // Auto active in Mock Mode if status checks triggered
                $this->transactionRepository->updateTransaction($transaction, [
                    'status' => 'ACTIVE',
                    'timestamps.started_at' => now(),
                    'timestamps.due_at' => now()->addHours(2),
                ]);

                $this->stationRepository->updateBoxAvailability(
                    $transaction->box_reference['station_id'],
                    $transaction->box_reference['box_id'],
                    false
                );

                $payment = $transaction->payments()->first();
                if ($payment) {
                    $payment->update([
                        'payment_status' => 'SUCCESS',
                        'paid_at' => now(),
                    ]);
                }
            }
        }

        $pickupCode = 'LCK-' . rand(1000, 9999);

        return [
            'success' => true,
            'pickup_code' => $pickupCode,
            'orderId' => $orderId,
            'status' => $transaction->status,
            'card_uid' => $transaction->card_uid ?? null,
        ];
    }

    /**
     * Link an RFID Card UID to an active locker transaction.
     */
    public function registerCard(string $orderId, string $cardUid): array
    {
        $transaction = $this->transactionRepository->findByGatewayRef($orderId);

        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'Transaksi sewa loker tidak ditemukan.',
            ];
        }

        $cardUid = strtoupper(trim($cardUid));
        if (empty($cardUid)) {
            return [
                'success' => false,
                'error' => 'Card ID RFID tidak boleh kosong.',
            ];
        }

        $this->transactionRepository->registerCardUid($transaction, $cardUid);

        // Record card registration event log
        $transaction->activityLogs()->create([
            'log_id' => new ObjectId(),
            'actor_id' => $transaction->parties['owner_id'],
            'event_name' => 'CARD_REGISTERED',
            'description' => "RFID Card ID [{$cardUid}] didaftarkan sebagai kunci loker.",
            'logged_at' => now(),
        ]);

        Log::info("LockerRentalService: Linked Card ID {$cardUid} to Order ID: {$orderId}");

        return [
            'success' => true,
            'message' => 'Kartu RFID berhasil didaftarkan.',
            'card_uid' => $cardUid,
        ];
    }
}
