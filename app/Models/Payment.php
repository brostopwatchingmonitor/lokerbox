<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Payment extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'payment_id', // ObjectId string
        'payment_method', // WALLET, TRANSFER, CASH, QRIS, CREDIT_CARD
        'payment_status', // PENDING, SUCCESS, FAILED, REFUNDED
        'amount',
        'gateway_ref',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];
}
