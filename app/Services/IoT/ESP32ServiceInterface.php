<?php

namespace App\Services\IoT;

interface ESP32ServiceInterface
{
    /**
     * Send unlock signal to the physical/simulated ESP32 board.
     *
     * @param string $stationId
     * @param int $boxNumber
     * @return bool
     */
    public function sendUnlockSignal(string $stationId, int $boxNumber): bool;

    /**
     * Simulate a card tap event.
     *
     * @param string $cardUid
     * @param int $ldrValue
     * @return array ['success' => bool, 'unlock' => bool, 'message' => string]
     */
    public function simulateCardTap(string $cardUid, int $ldrValue): array;
}
