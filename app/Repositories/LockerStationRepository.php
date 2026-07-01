<?php

namespace App\Repositories;

use App\Models\LockerStation;
use Illuminate\Support\Facades\Log;

class LockerStationRepository implements LockerStationRepositoryInterface
{
    /**
     * Find a locker station by ID.
     */
    public function findStation(string $stationId): ?LockerStation
    {
        return LockerStation::find($stationId);
    }

    /**
     * Update locker box availability.
     */
    public function updateBoxAvailability(string $stationId, string $boxId, bool $isAvailable): bool
    {
        try {
            $station = LockerStation::find($stationId);
            if ($station instanceof LockerStation) {
                $boxes = $station->boxes;
                $updated = false;
                foreach ($boxes as $box) {
                    if ($box->box_id == $boxId) {
                        $box->is_available = (bool) $isAvailable;
                        $updated = true;
                    }
                }
                if ($updated) {
                    $station->boxes = $boxes;
                    $station->save();
                    Log::info("Repository: Box {$boxId} availability updated to: " . ($isAvailable ? 'AVAILABLE' : 'OCCUPIED'));
                    return true;
                }
            }
            Log::warning("Repository: Station {$stationId} not found or box {$boxId} not matching.");
            return false;
        } catch (\Exception $e) {
            Log::error('Repository: Failed to update box availability: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find an available box of a certain size type at a station.
     */
    public function findAvailableBox(string $stationId, string $sizeType): ?string
    {
        try {
            $station = LockerStation::find($stationId);
            if ($station) {
                foreach ($station->boxes as $box) {
                    if (strtoupper($box->size_type) === strtoupper($sizeType) && $box->is_available) {
                        return $box->box_id;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Repository: Could not query available boxes: ' . $e->getMessage());
        }
        return null;
    }
}
