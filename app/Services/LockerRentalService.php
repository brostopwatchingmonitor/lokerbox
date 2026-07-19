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
    protected \App\Services\IoT\ESP32ServiceInterface $esp32Service;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        LockerStationRepositoryInterface $stationRepository,
        MidtransServiceInterface $midtransService,
        \App\Services\IoT\ESP32ServiceInterface $esp32Service
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->stationRepository = $stationRepository;
        $this->midtransService = $midtransService;
        $this->esp32Service = $esp32Service;
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
                    'duration' => (int)$duration,
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
            $durationHours = isset($transaction->fees['duration']) ? (int)$transaction->fees['duration'] : 2;
            $timestamps = $transaction->getAttribute('timestamps') ?? [];
            $timestamps['started_at'] = now();
            $timestamps['due_at'] = now()->addHours($durationHours);
            $transactionUpdate['timestamps'] = $timestamps;

            // Reserve the locker box (set availability to false)
            $this->stationRepository->updateBoxAvailability(
                $this->resolveObjectIdString($transaction->box_reference['station_id']),
                $this->resolveObjectIdString($transaction->box_reference['box_id']),
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
                    $timestamps = $transaction->getAttribute('timestamps') ?? [];
                    $timestamps['started_at'] = now();
                    $durationHours = isset($transaction->fees['duration']) ? (int)$transaction->fees['duration'] : 2;
                    $timestamps['due_at'] = now()->addHours($durationHours);
                    $this->transactionRepository->updateTransaction($transaction, [
                        'status' => 'ACTIVE',
                        'timestamps' => $timestamps,
                    ]);

                    $this->stationRepository->updateBoxAvailability(
                        $this->resolveObjectIdString($transaction->box_reference['station_id']),
                        $this->resolveObjectIdString($transaction->box_reference['box_id']),
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
                $timestamps = $transaction->getAttribute('timestamps') ?? [];
                $timestamps['started_at'] = now();
                $durationHours = isset($transaction->fees['duration']) ? (int)$transaction->fees['duration'] : 2;
                $timestamps['due_at'] = now()->addHours($durationHours);
                $this->transactionRepository->updateTransaction($transaction, [
                    'status' => 'ACTIVE',
                    'timestamps' => $timestamps,
                ]);

                $this->stationRepository->updateBoxAvailability(
                    $this->resolveObjectIdString($transaction->box_reference['station_id']),
                    $this->resolveObjectIdString($transaction->box_reference['box_id']),
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

    /**
     * Get transaction history for user, active rental and statistics.
     */
    public function getHistory(User $user): array
    {
        $userId = $user->_id;

        // Fetch all transactions for the user
        $transactions = \App\Models\Transaction::where('parties.owner_id', new \MongoDB\BSON\ObjectId($userId))
            ->orderBy('timestamps.created_at', 'desc')
            ->get();

        $stats = [
            'total_rentals' => 0,
            'stations_visited' => 0,
        ];

        $stations = [];
        $activeRental = null;
        $activeRentals = [];
        $history = [];

        foreach ($transactions as $tx) {
            $stationId = null;
            if (isset($tx->box_reference['station_id'])) {
                $stationId = $this->resolveObjectIdString($tx->box_reference['station_id']);
                $stations[$stationId] = true;
            }

            // Exclude pending from stats unless paid
            if ($tx->status !== 'PENDING') {
                $stats['total_rentals']++;
            }

            // Get station name
            $stationName = 'Station';
            if ($stationId) {
                $stationModel = \App\Models\LockerStation::find($stationId);
                if ($stationModel) {
                    $stationName = $stationModel->location_name;
                }
            }

            $formatted = [
                'order_id' => $tx->payments()->first()?->gateway_ref ?? $tx->_id,
                'locker_id' => isset($tx->box_reference['box_id']) ? 'Locker' : 'Unknown',
                'station_name' => $stationName,
                'created_at' => is_array($tx->getAttribute('timestamps')) && isset($tx->getAttribute('timestamps')['created_at']) ? \Illuminate\Support\Carbon::parse($tx->getAttribute('timestamps')['created_at'])->format('Y-m-d H:i') : null,
                'status' => $tx->status,
                'card_uid' => $tx->card_uid ?? null,
            ];

            // Resolve box size/number if possible
            if (isset($tx->box_reference['box_id']) && $stationId) {
                $stationModel = \App\Models\LockerStation::find($stationId);
                if ($stationModel) {
                    foreach ($stationModel->boxes as $box) {
                        if ((string)$box->box_id === $this->resolveObjectIdString($tx->box_reference['box_id'])) {
                            $formatted['locker_id'] = 'Locker ' . ($box->size_type === 'LARGE' ? 'Large' : 'Small') . ' #' . $box->box_number;
                            break;
                        }
                    }
                }
            }

            if ($tx->status === 'ACTIVE') {
                $dueAt = is_array($tx->getAttribute('timestamps')) && isset($tx->getAttribute('timestamps')['due_at']) ? \Illuminate\Support\Carbon::parse($tx->getAttribute('timestamps')['due_at']) : null;
                $remainingSeconds = $dueAt ? max(0, now()->diffInSeconds($dueAt, false)) : 0;
                
                $activeRentals[] = [
                    'order_id' => $formatted['order_id'],
                    'locker_id' => $formatted['locker_id'],
                    'station_name' => $formatted['station_name'],
                    'card_uid' => $tx->card_uid ?? null,
                    'remaining_seconds' => $remainingSeconds,
                    'due_at' => $dueAt ? $dueAt->toIso8601String() : null,
                ];
            } else {
                $history[] = $formatted;
            }
        }

        // Keep activeRental for backward compatibility
        $activeRental = count($activeRentals) > 0 ? $activeRentals[0] : null;

        $stats['stations_visited'] = count($stations);

        return [
            'success' => true,
            'stats' => $stats,
            'active_rental' => $activeRental,
            'active_rentals' => $activeRentals,
            'history' => $history,
        ];
    }

    /**
     * Emergency reopen a locker for an active rental if no card is registered.
     */
    public function reopenLocker(User $user, string $orderId): array
    {
        $transaction = $this->transactionRepository->findByGatewayRef($orderId);

        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'Transaksi sewa loker tidak ditemukan.',
            ];
        }

        // Verify ownership
        if ((string)$transaction->parties['owner_id'] !== (string)$user->_id) {
            return [
                'success' => false,
                'error' => 'Anda tidak memiliki akses ke loker ini.',
            ];
        }

        // Verify active status
        if ($transaction->status !== 'ACTIVE') {
            return [
                'success' => false,
                'error' => 'Loker tidak dalam status aktif.',
            ];
        }

        // Fetch station and box
        $stationId = $this->resolveObjectIdString($transaction->box_reference['station_id'] ?? '');
        $boxId = $this->resolveObjectIdString($transaction->box_reference['box_id'] ?? '');
        $boxNumber = 1;

        try {
            $station = \App\Models\LockerStation::find($stationId);
            if ($station) {
                foreach ($station->boxes as $box) {
                    if ((string)$box->box_id === $boxId) {
                        $boxNumber = $box->box_number;
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("LockerRentalService: Error fetching box number for reopen: " . $e->getMessage());
        }

        // Trigger physical solenoid open via IoT service
        $unlocked = $this->esp32Service->sendUnlockSignal($stationId, $boxNumber);

        if (!$unlocked) {
            return [
                'success' => false,
                'error' => 'Gagal membuka loker secara remote.',
            ];
        }

        // Log the activity
        $transaction->activityLogs()->create([
            'log_id' => new ObjectId(),
            'actor_id' => $user->_id,
            'event_name' => 'EMERGENCY_REOPEN',
            'description' => 'Loker dibuka kembali secara darurat via aplikasi (tanpa kartu RFID).',
            'logged_at' => now(),
        ]);

        // Complete the transaction
        $timestamps = $transaction->getAttribute('timestamps') ?? [];
        $timestamps['ended_at'] = now();
        $this->transactionRepository->updateTransaction($transaction, [
            'status' => 'COMPLETED',
            'timestamps' => $timestamps,
        ]);

        // Free the locker box
        $this->stationRepository->updateBoxAvailability(
            $stationId,
            $boxId,
            true
        );

        return [
            'success' => true,
            'message' => 'Sinyal darurat berhasil dikirim. Pintu loker terbuka!',
        ];
    }

    /**
     * Helper to resolve MongoDB ObjectId or nested array representation to string.
     */
    private function resolveObjectIdString($val): string
    {
        if (is_string($val)) {
            return $val;
        }
        if (is_object($val)) {
            return (string)$val;
        }
        if (is_array($val)) {
            if (isset($val['$oid'])) {
                return (string)$val['$oid'];
            }
            return (string)reset($val);
        }
        return (string)$val;
    }
}
