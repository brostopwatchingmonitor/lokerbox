<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AuthorizedUser extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'auth_id', // ObjectId string
        'user_id', // ObjectId string referensi ke User
        'status', // ACTIVE, EXPIRED, REVOKED
        'granted_at',
        'expires_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
