<?php

namespace App\Http\Controllers;

use App\Models\LockerStation;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;

class LockerRentalController extends Controller
{
    /**
     * Membuat order sewa loker dan mengambil token Midtrans Snap.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'lockerSize' => 'required|string|in:Small,Large',
            'duration' => 'required|integer|min:1',
            'price' => 'required|numeric',
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'User not authenticated'], 401);
        }

        $orderId = 'LKR-'.time().'-'.rand(100, 999);
        $totalPrice = $request->price * $request->duration;

        // 1. Simpan Transaksi Baru ke MongoDB
        try {
            // Find available locker box of requested size, fallback to hardcoded test values if not found/seeded
            $stationId = new ObjectId('6647a123f1b4c3d2e1a8b002');
            $boxId = new ObjectId('6647a123f1b4c3d2e1a8b003'); // fallback to Small

            try {
                $station = LockerStation::find($stationId);
                if ($station) {
                    $assignedBox = null;
                    foreach ($station->boxes as $box) {
                        if ($box->size_type === $request->lockerSize && $box->is_available) {
                            $assignedBox = $box;
                            break;
                        }
                    }
                    if ($assignedBox) {
                        $boxId = new ObjectId($assignedBox->box_id);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not find dynamic box, falling back to default box: '.$e->getMessage());
            }

            $transaction = Transaction::create([
                'box_reference' => [
                    'station_id' => $stationId,
                    'box_id' => $boxId,
                ],
                'parties' => [
                    'owner_id' => new ObjectId($user->_id),
                    'sender_id' => null,
                ],
                'transaction_type' => 'SELF_USE',
                'status' => 'PENDING',
                'fees' => [
                    'base_fee' => (float) $request->price,
                    'penalty_fee' => 0.00,
                    'total_fee' => (float) $totalPrice,
                ],
                'timestamps' => [
                    'created_at' => now(),
                    'started_at' => null,
                    'due_at' => null,
                    'ended_at' => null,
                ],
            ]);

            // Tambahkan data pembayaran awal (pending)
            $transaction->payments()->create([
                'payment_id' => new ObjectId,
                'payment_method' => 'MIDTRANS',
                'payment_status' => 'PENDING',
                'amount' => (float) $totalPrice,
                'gateway_ref' => $orderId,
                'paid_at' => null,
            ]);

        } catch (\Exception $e) {
            Log::error('MongoDB Error (Create Order): '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Gagal menyimpan transaksi ke database: '.$e->getMessage(),
            ], 500);
        }

        // 2. Hubungi API Midtrans Sandbox
        $serverKey = config('services.midtrans.server_key');
        $clientKey = config('services.midtrans.client_key');
        $isProduction = filter_var(config('services.midtrans.is_production'), FILTER_VALIDATE_BOOLEAN);

        // FITUR FALLBACK / MOCK MODE:
        if (empty($serverKey) || $serverKey === 'YOUR_MIDTRANS_SERVER_KEY') {
            Log::info('Midtrans Server Key is empty. Running in MOCK Mode for Order: '.$orderId);

            return response()->json([
                'success' => true,
                'token' => 'mock-snap-token-'.uniqid(),
                'orderId' => $orderId,
                'isMock' => true,
            ]);
        }

        try {
            $authHeader = base64_encode($serverKey.':');
            $baseUrl = $isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $response = Http::withHeaders([
                'Authorization' => 'Basic '.$authHeader,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($baseUrl, [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $totalPrice,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->fcm_token ?? '08123456789',
                ],
                'item_details' => [
                    [
                        'id' => $request->lockerSize,
                        'price' => $request->price,
                        'quantity' => $request->duration,
                        'name' => 'Sewa Loker '.$request->lockerSize.' ('.$request->duration.' Jam)',
                    ],
                ],
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['token'])) {
                return response()->json([
                    'success' => true,
                    'token' => $result['token'],
                    'orderId' => $orderId,
                ]);
            } else {
                Log::error('Midtrans API Request Error: '.json_encode($result));

                return response()->json([
                    'success' => false,
                    'error' => 'Midtrans Error: '.($result['error_messages'][0] ?? 'Gagal membuat transaksi'),
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Connection Error (Midtrans): '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Kesalahan koneksi ke Midtrans: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menerima notifikasi webhook/callback dari Midtrans secara asinkron.
     */
    public function handleNotification(Request $request): JsonResponse
    {
        Log::info('Midtrans Webhook Callback Triggered.');

        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
            return response()->json(['success' => false, 'error' => 'Invalid payload parameters'], 400);
        }

        // 1. Verifikasi Keamanan Signature Key
        $serverKey = config('services.midtrans.server_key');
        $localSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if ($localSignature !== $signatureKey) {
            Log::warning('Midtrans Webhook: Invalid Signature Key. Potential tampering detected.');

            return response()->json(['success' => false, 'error' => 'Invalid signature key'], 403);
        }

        Log::info("Signature verified. Processing Transaction Order ID: {$orderId}. Status: {$transactionStatus}");

        // 2. Temukan Transaksi di MongoDB
        $transaction = Transaction::where('payments.gateway_ref', $orderId)->first();

        if (! $transaction) {
            Log::error("Transaction not found for Order ID: {$orderId}");

            return response()->json(['success' => false, 'error' => 'Transaction not found'], 404);
        }

        // 3. Tentukan status pembayaran & status loker
        $paymentStatus = 'PENDING';
        $lokerStatus = 'PENDING';
        $isPaid = false;

        if ($transactionStatus == 'capture') {
            if ($paymentType == 'credit_card') {
                if ($fraudStatus == 'challenge') {
                    $paymentStatus = 'PENDING';
                } else {
                    $paymentStatus = 'SUCCESS';
                    $lokerStatus = 'ACTIVE';
                    $isPaid = true;
                }
            }
        } elseif ($transactionStatus == 'settlement') {
            $paymentStatus = 'SUCCESS';
            $lokerStatus = 'ACTIVE';
            $isPaid = true;
        } elseif ($transactionStatus == 'pending') {
            $paymentStatus = 'PENDING';
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $paymentStatus = 'FAILED';
            $lokerStatus = 'CANCELLED';
        }

        // 4. Update status transaksi utama di MongoDB
        $transactionUpdate = [
            'status' => $lokerStatus,
        ];

        if ($isPaid) {
            $durationHours = 2; // Default fallback durasi sewa
            $transactionUpdate['timestamps.started_at'] = now();
            $transactionUpdate['timestamps.due_at'] = now()->addHours($durationHours);

            // Set locker box availability to false (occupied)
            $this->updateBoxAvailability(
                $transaction->box_reference['station_id'],
                $transaction->box_reference['box_id'],
                false
            );
        }

        $transaction->update($transactionUpdate);

        // 5. Update status pembayaran bersarang (payments array)
        $payment = $transaction->payments()->first();
        if ($payment) {
            $payment->update([
                'payment_status' => $paymentStatus,
                'paid_at' => $isPaid ? now() : null,
            ]);
        }

        // 6. Catat aktivitas dalam sub-dokumen activity_logs
        $transaction->activityLogs()->create([
            'log_id' => new ObjectId,
            'actor_id' => $transaction->parties['owner_id'],
            'event_name' => $isPaid ? 'PAYMENT_SUCCESS' : 'PAYMENT_STATUS_UPDATE',
            'description' => "Status pembayaran diperbarui menjadi: {$paymentStatus}. Status Loker: {$lokerStatus}",
            'metadata_iot' => [
                'midtrans_transaction_id' => $payload['transaction_id'] ?? '',
                'payment_type' => $paymentType,
                'status_message' => $payload['status_message'] ?? '',
            ],
            'logged_at' => now(),
        ]);

        Log::info("Transaction {$orderId} successfully processed and updated in MongoDB.");

        return response()->json(['success' => true]);
    }

    /**
     * Mengambil kode pengambilan loker (pickup code) setelah pembayaran berhasil.
     */
    public function getPickupCode(string $orderId): JsonResponse
    {
        $transaction = Transaction::where('payments.gateway_ref', $orderId)->first();

        if (! $transaction) {
            // MOCK: Jika database tidak menyala/ditemukan, kembalikan kode dummy agar user tetap bisa demo
            return response()->json([
                'success' => true,
                'pickup_code' => 'LCK-'.rand(1000, 9999),
                'orderId' => $orderId,
                'status' => 'SUCCESS',
            ]);
        }

        if ($transaction->status === 'PENDING') {
            $serverKey = config('services.midtrans.server_key');
            if (! empty($serverKey) && $serverKey !== 'YOUR_MIDTRANS_SERVER_KEY') {
                try {
                    $authHeader = base64_encode($serverKey.':');
                    $baseUrl = filter_var(config('services.midtrans.is_production'), FILTER_VALIDATE_BOOLEAN)
                        ? "https://api.midtrans.com/v2/{$orderId}/status"
                        : "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

                    $statusResponse = Http::withHeaders(['Authorization' => 'Basic '.$authHeader])->get($baseUrl);
                    $statusResult = $statusResponse->json();

                    if ($statusResponse->successful() && isset($statusResult['transaction_status'])) {
                        $txStatus = $statusResult['transaction_status'];
                        if (in_array($txStatus, ['settlement', 'capture'])) {
                            $transaction->update([
                                'status' => 'ACTIVE',
                                'timestamps.started_at' => now(),
                                'timestamps.due_at' => now()->addHours(2),
                            ]);
                            // Set locker box availability to false (occupied)
                            $this->updateBoxAvailability(
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
                } catch (\Exception $e) {
                    Log::error('Synchronous check failed: '.$e->getMessage());
                }
            } else {
                // MOCK MODE: transition to ACTIVE directly
                $transaction->update([
                    'status' => 'ACTIVE',
                    'timestamps.started_at' => now(),
                    'timestamps.due_at' => now()->addHours(2),
                ]);
                // Set locker box availability to false (occupied)
                $this->updateBoxAvailability(
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

        $pickupCode = 'LCK-'.rand(1000, 9999);

        // Jika transaksi disetujui, return kode pickup loker
        $status = $transaction->status;

        return response()->json([
            'success' => true,
            'pickup_code' => $pickupCode,
            'orderId' => $orderId,
            'status' => $status,
        ]);
    }

    /**
     * Helper untuk mengubah status ketersediaan locker box di database secara aman.
     */
    private function updateBoxAvailability(mixed $stationId, mixed $boxId, bool $isAvailable): void
    {
        // Konversi stationId ke string jika berupa array (seperti ['$oid' => '...']) atau objek ObjectId
        $stationIdStr = null;
        if (is_array($stationId)) {
            $stationIdStr = $stationId['$oid'] ?? (string) ($stationId[0] ?? '');
        } else {
            $stationIdStr = (string) $stationId;
        }

        // Konversi boxId ke string jika berupa array (seperti ['$oid' => '...']) atau objek ObjectId
        $boxIdStr = null;
        if (is_array($boxId)) {
            $boxIdStr = $boxId['$oid'] ?? (string) ($boxId[0] ?? '');
        } else {
            $boxIdStr = (string) $boxId;
        }

        try {
            $station = LockerStation::find($stationIdStr);
            if ($station instanceof LockerStation) {
                $boxes = $station->boxes;
                $updated = false;
                foreach ($boxes as $box) {
                    if ($box->box_id == $boxIdStr) {
                        $box->is_available = (bool) $isAvailable;
                        $updated = true;
                    }
                }
                if ($updated) {
                    $station->boxes = $boxes;
                    $station->save();
                    Log::info("Box {$boxIdStr} availability set to ".($isAvailable ? 'true' : 'false'));
                }
            } else {
                Log::warning("LockerStation {$stationIdStr} not found for updating box availability.");
            }
        } catch (\Exception $e) {
            Log::error('Failed to update box availability: '.$e->getMessage());
        }
    }

    /**
     * Menerima tap kartu RFID dari board Arduino fisik untuk otentikasi buka kunci solenoid.
     */
    public function tapCard(Request $request): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string',
            'ldr_value' => 'required|integer',
        ]);

        $cardUid = strtoupper($request->uid);
        $ldrValue = $request->ldr_value;

        Log::info("Arduino Tap RFID: UID [{$cardUid}], LDR [{$ldrValue}]");

        // Cari transaksi sewa loker aktif (status = ACTIVE)
        // Kita juga mencocokkan UID kartu yang terdaftar pada transaksi tersebut
        // Di sini kita mencocokkan jika properti metadata 'card_uid' pada transaksi bernilai sama
        $transaction = Transaction::where('status', 'ACTIVE')
            ->where('card_uid', $cardUid)
            ->first();

        // MOCK/DEMO MODE: Jika data transaksi tidak ditemukan, kita izinkan kartu UID test apa pun (seperti '1A2B3C4D') agar demo hardware tetap berjalan
        if (! $transaction) {
            if ($cardUid === '1A2B3C4D' || $cardUid === 'TESTCARD123') {
                Log::info("Mock RFID Accept: Card {$cardUid} verified as test card.");

                return response()->json([
                    'success' => true,
                    'unlock' => true,
                    'message' => 'Loker dibuka (Mock Mode)',
                ]);
            }

            Log::warning("Access Denied: No active transaction found for RFID UID {$cardUid}");

            return response()->json([
                'success' => true,
                'unlock' => false,
                'message' => 'Akses ditolak. Kartu tidak terdaftar.',
            ]);
        }

        // Tulis log aktivitas sensor LDR (intensitas cahaya) dan status solenoid ke activity_logs MongoDB
        $transaction->activityLogs()->create([
            'log_id' => new ObjectId,
            'actor_id' => $transaction->parties['owner_id'],
            'event_name' => 'BOX_OPENED',
            'description' => "Loker berhasil dibuka via tap kartu RFID. Sensor LDR: {$ldrValue} (terang/gelap).",
            'metadata_iot' => [
                'rfid_uid' => $cardUid,
                'sensor_ldr_raw' => $ldrValue,
                'box_opened_status' => true,
            ],
            'logged_at' => now(),
        ]);

        Log::info("Access Approved: Solenoid unlocked for transaction ID: {$transaction->_id}");

        return response()->json([
            'success' => true,
            'unlock' => true,
            'message' => 'Akses disetujui. Solenoid terbuka.',
        ]);
    }
}
