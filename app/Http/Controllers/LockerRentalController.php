<?php

namespace App\Http\Controllers;

use App\Services\LockerRentalService;
use App\Services\IoT\ESP32ServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LockerRentalController extends Controller
{
    protected LockerRentalService $lockerRentalService;
    protected ESP32ServiceInterface $esp32Service;

    public function __construct(
        LockerRentalService $lockerRentalService,
        ESP32ServiceInterface $esp32Service
    ) {
        $this->lockerRentalService = $lockerRentalService;
        $this->esp32Service = $esp32Service;
    }

    /**
     * Create locker rental order and generate Midtrans Snap Token.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'lockerSize' => 'required|string|in:Small,Large',
            'duration'   => 'required|integer|min:1',
            'price'      => 'required|numeric',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error'   => 'User tidak terotentikasi.'
            ], 401);
        }

        $result = $this->lockerRentalService->createRentalOrder(
            $user,
            $request->lockerSize,
            (int)$request->duration,
            (float)$request->price
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Handle asynchronous webhook notification from Midtrans.
     */
    public function handleNotification(Request $request): JsonResponse
    {
        Log::info('Webhook: Midtrans notification webhook triggered.');
        $payload = $request->all();

        $success = $this->lockerRentalService->handlePaymentWebhook($payload);

        if (!$success) {
            return response()->json([
                'success' => false,
                'error'   => 'Gagal memproses notifikasi pembayaran.'
            ], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Retrieve locker pickup code (verifies status synchronously if needed).
     */
    public function getPickupCode(string $orderId): JsonResponse
    {
        $result = $this->lockerRentalService->retrievePickupCode($orderId);
        return response()->json($result);
    }

    /**
     * Register a Card UID to a locker rental transaction (for simulated/hardware link).
     */
    public function registerCard(Request $request): JsonResponse
    {
        $request->validate([
            'orderId' => 'required|string',
            'cardUid' => 'required|string|min:4',
        ]);

        $result = $this->lockerRentalService->registerCard(
            $request->orderId,
            $request->cardUid
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Receive RFID card tap from simulated UI or actual physical Arduino ESP32 scanner.
     */
    public function tapCard(Request $request): JsonResponse
    {
        $request->validate([
            'uid'       => 'required|string',
            'ldr_value' => 'required|integer',
        ]);

        Log::info("Arduino/Simulated Tap: Card UID [{$request->uid}], LDR value [{$request->ldr_value}]");

        $result = $this->esp32Service->simulateCardTap(
            $request->uid,
            (int)$request->ldr_value
        );

        return response()->json($result);
    }
}
