<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;
use MongoDB\Laravel\Relations\EmbedsMany;

class Transaction extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'transactions';

    protected $fillable = [
        'box_reference',      // ['station_id' => ObjectId, 'box_id' => ObjectId]
        'parties',            // ['owner_id' => ObjectId, 'sender_id' => ObjectId|null]
        'transaction_type',   // SELF_USE, SEND, TITIP
        'status',             // PENDING, ACTIVE, COMPLETED, CANCELLED, OVERDUE
        'fees',               // ['base_fee' => decimal, 'penalty_fee' => decimal, 'total_fee' => decimal]
        'timestamps',         // ['created_at' => Date, 'started_at' => Date|null, 'due_at' => Date|null, 'ended_at' => Date|null]
    ];

    protected $casts = [
        'box_reference' => 'array',
        'parties' => 'array',
        'fees' => 'array',
        'timestamps' => 'array',
    ];

    // --- Relasi Referensi (References) ---

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parties.owner_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parties.sender_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(LockerStation::class, 'box_reference.station_id');
    }

    // --- Relasi Dokumen Bersarang (Embeds Many) ---

    public function payments(): EmbedsMany
    {
        return $this->embedsMany(Payment::class);
    }

    public function authorizedUsers(): EmbedsMany
    {
        return $this->embedsMany(AuthorizedUser::class);
    }

    public function activityLogs(): EmbedsMany
    {
        return $this->embedsMany(ActivityLog::class);
    }
}
