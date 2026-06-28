<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $box_id
 * @property int $box_number
 * @property string $size_type
 * @property bool $is_available
 * @property string $door_status
 * @property float $price_per_hour
 */
class LockerBox extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'box_id', // ObjectId string
        'box_number',
        'size_type', // SMALL, MEDIUM, LARGE
        'is_available',
        'door_status', // LOCKED, UNLOCKED, ERROR
        'price_per_hour',
    ];

    protected $casts = [
        'box_id' => 'string',
        'is_available' => 'boolean',
        'price_per_hour' => 'decimal:2',
    ];
}
