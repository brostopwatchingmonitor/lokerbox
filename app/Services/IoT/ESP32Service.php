<?php

namespace App\Services\IoT;

use App\Repositories\TransactionRepositoryInterface;
use App\Models\LockerStation;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;

class ESP32Service implements ESP32ServiceInterface
{
    protected TransactionRepositoryInterface $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Send unlock signal to the physical/simulated ESP32 board.
     */
    public function sendUnlockSignal(string $stationId, int $boxNumber): bool
    {
        // -----------------------------------------------------------------------------------
        // ESP32 FUTURE microchip INTEGRATION LOGIC (MQTT / WebSockets / HTTP API)
        // -----------------------------------------------------------------------------------
        // Keep the design implementation template to future-proof communication with the
        // hardware. In the simulation phase, it writes detail mock execution logs.

        Log::info("IoT Service: Preparing 'UNLOCK' command - Station ID: {$stationId}, Box Number: {$boxNumber}");

        // --- PROTOCOL 1: MQTT PUBLISH (RECOMMENDED FOR STABLE WORKPLACES) ---
        /*
        try {
            $mqttBroker = config('services.iot.mqtt_broker', 'broker.hivemq.com');
            $mqttPort = config('services.iot.mqtt_port', 1883);
            $clientId = 'lokerbox_backend_' . uniqid();
            
            $mqtt = new \PhpMqtt\Client\MqttClient($mqttBroker, $mqttPort, $clientId);
            $mqtt->connect();
            
            $payload = json_encode([
                'station_id' => $stationId,
                'box_number' => $boxNumber,
                'command'    => 'UNLOCK_SOLENOID',
                'pulse_ms'   => 3000, // keep unlocked for 3 seconds
                'timestamp'  => now()->timestamp
            ]);
            
            $mqtt->publish("locker/station/{$stationId}/control", $payload, 1); // QoS 1 (At least once)
            $mqtt->disconnect();
            
            Log::info("IoT: Published unlock message to MQTT topic [locker/station/{$stationId}/control]");
        } catch (\Exception $e) {
            Log::error("IoT MQTT publish failed: " . $e->getMessage());
        }
        */

        // --- PROTOCOL 2: LARAVEL WEBSOCKETS (LARAVEL REVERB / PUSHER) ---
        /*
        try {
            // ESP32 client connects to Reverb/Pusher WS server and listens to this event.
            // When broadcasted, WS server pushes JSON command payload to active client connection.
            event(new \App\Events\SolenoidUnlockTriggered($stationId, $boxNumber));
            Log::info("IoT: WebSocket event broadcasted for Station: {$stationId}, Box: {$boxNumber}");
        } catch (\Exception $e) {
            Log::error("IoT WebSocket broadcast failed: " . $e->getMessage());
        }
        */

        // --- PROTOCOL 3: DIRECT HTTP REQUEST (ESP32 RUNS WEB SERVER) ---
        /*
        try {
            // Send direct HTTP call if ESP32 runs a local HTTP listener on local WiFi network
            $espIp = "http://192.168.1.150"; // resolved or config-bound
            $response = \Illuminate\Support\Facades\Http::timeout(3)->post("{$espIp}/api/unlock", [
                'box_number' => $boxNumber
            ]);
            if ($response->successful()) {
                Log::info("IoT: Solenoid unlock HTTP request accepted by ESP32 server.");
            }
        } catch (\Exception $e) {
            Log::warning("IoT: HTTP direct endpoint unreachable: " . $e->getMessage());
        }
        */

        Log::info("IoT SIMULATION: Unlock signal sent successfully to simulated ESP32 (Solenoid unlocked).");
        return true;
    }

    /**
     * Simulate a card tap event.
     */
    public function simulateCardTap(string $cardUid, int $ldrValue): array
    {
        $cardUid = strtoupper(trim($cardUid));
        Log::info("IoT Service (Simulated Tap): Card UID [{$cardUid}], LDR Sensor Value [{$ldrValue}]");

        // 1. Locate active transaction registered to this RFID card UID
        $transaction = $this->transactionRepository->findActiveByCardUid($cardUid);

        if (!$transaction) {
            // MOCK/DEMO BACKWARD COMPATIBILITY: Allow testing with dummy cards ('1A2B3C4D', 'TESTCARD123')
            if ($cardUid === '1A2B3C4D' || $cardUid === 'TESTCARD123') {
                Log::info("IoT Service (Simulated Tap): Demo card matched. Simulating unlock command.");
                
                // Unlocks box #3 of our default station
                $this->sendUnlockSignal('6647a123f1b4c3d2e1a8b002', 3);

                return [
                    'success' => true,
                    'unlock'  => true,
                    'message' => 'Akses Loker disetujui (Mode Demo - Kartu Uji Coba)',
                ];
            }

            Log::warning("IoT Service (Simulated Tap): Access Denied. No active transaction for Card UID: {$cardUid}");
            return [
                'success' => true,
                'unlock'  => false,
                'message' => 'Akses ditolak. Kartu RFID belum didaftarkan pada transaksi sewa aktif Anda.',
            ];
        }

        // 2. Fetch related box number from Station
        $stationId = (string)($transaction->box_reference['station_id'] ?? '');
        $boxId = (string)($transaction->box_reference['box_id'] ?? '');
        $boxNumber = 1;

        try {
            $station = LockerStation::find($stationId);
            if ($station) {
                foreach ($station->boxes as $box) {
                    if ((string)$box->box_id === $boxId) {
                        $boxNumber = $box->box_number;
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("IoT Service (Simulated Tap): Error fetching box number: " . $e->getMessage());
        }

        // 3. Write activity log inside MongoDB Transaction model
        $transaction->activityLogs()->create([
            'log_id'       => new ObjectId(),
            'actor_id'     => $transaction->parties['owner_id'],
            'event_name'   => 'BOX_OPENED',
            'description'  => "Loker berhasil dibuka via tap kartu RFID (Simulasi). Sensor LDR: {$ldrValue} (terang/gelap).",
            'metadata_iot' => [
                'rfid_uid'          => $cardUid,
                'sensor_ldr_raw'    => $ldrValue,
                'box_opened_status' => true,
                'simulated'         => true
            ],
            'logged_at'    => now(),
        ]);

        // 4. Send the signal (logs it + triggers future integration)
        $this->sendUnlockSignal($stationId, $boxNumber);

        Log::info("IoT Service (Simulated Tap): Success. Access approved, locker door unlocked.");
        return [
            'success' => true,
            'unlock'  => true,
            'message' => 'Akses disetujui. Solenoid loker terbuka.',
        ];
    }
}
