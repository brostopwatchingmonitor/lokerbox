<?php

namespace App\Repositories;

use App\Models\LockerStation;

interface LockerStationRepositoryInterface
{
    /**
     * Find a locker station by ID.
     *
     * @param string $stationId
     * @return LockerStation|null
     */
    public function findStation(string $stationId): ?LockerStation;

    /**
     * Update locker box availability.
     *
     * @param string $stationId
     * @param string $boxId
     * @param bool $isAvailable
     * @return bool
     */
    public function updateBoxAvailability(string $stationId, string $boxId, bool $isAvailable): bool;

    /**
     * Find an available box of a certain size type at a station.
     *
     * @param string $stationId
     * @param string $sizeType
     * @return string|null The box_id
     */
    public function findAvailableBox(string $stationId, string $sizeType): ?string;
}
