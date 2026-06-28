<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\EmbedsMany;

/**
 * @property string $_id
 * @property string $location_name
 * @property array<string, mixed> $location_geom
 * @property string $connectivity_status
 * @property Carbon|null $last_heartbeat
 * @property Collection<int, LockerBox> $boxes
 */
class LockerStation extends Model
{
    protected $connection = 'mongodb';

    protected string $collection = 'locker_stations';

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
     *
     * @return EmbedsMany<LockerBox, LockerStation, mixed>
     */
    public function boxes(): EmbedsMany
    {
        return $this->embedsMany(LockerBox::class);
    }
}
