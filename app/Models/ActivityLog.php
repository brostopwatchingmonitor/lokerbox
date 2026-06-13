<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ActivityLog extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'log_id', // ObjectId string
        'actor_id', // ObjectId string referensi ke User
        'event_name', // e.g. BOX_OPENED, OVERDUE_DETECTED
        'description',
        'metadata_iot',
        'logged_at',
    ];

    protected $casts = [
        'metadata_iot' => 'array',
        'logged_at' => 'datetime',
    ];
}
