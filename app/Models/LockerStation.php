<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\EmbedsMany;

class LockerStation extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'locker_stations';

    protected $fillable = [
        'location_name',
        'location_geom', // Standard GeoJSON Point ['type' => 'Point', 'coordinates' => [lng, lat]]
        'connectivity_status', // ONLINE, OFFLINE, MAINTENANCE
        'last_heartbeat',
    ];

    protected $casts = [
        'location_geom' => 'array',
        'last_heartbeat' => 'datetime',
    ];

    /**
     * Relasi Embedded Many ke LockerBox
     */
    public function boxes(): EmbedsMany
    {
        return $this->embedsMany(LockerBox::class);
    }
}
