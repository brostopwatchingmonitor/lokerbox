<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\EmbedsMany;

/**
 * @property string $_id
 * @property array<string, mixed> $box_reference
 * @property array<string, mixed> $parties
 * @property string $transaction_type
 * @property string $status
 * @property array<string, mixed> $fees
 * @property array<string, mixed> $timestamps
 * @property string|null $card_uid
 */
class Transaction extends Model
{
    protected $connection = 'mongodb';

    protected string $collection = 'transactions';

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'parties.owner_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function sender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'parties.sender_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<LockerStation, $this>
     */
    public function station(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LockerStation::class, 'box_reference.station_id');
    }

    // --- Relasi Dokumen Bersarang (Embeds Many) ---

    /**
     * @return EmbedsMany<Payment, Transaction, mixed>
     */
    public function payments(): EmbedsMany
    {
        return $this->embedsMany(Payment::class);
    }

    /**
     * @return EmbedsMany<AuthorizedUser, Transaction, mixed>
     */
    public function authorizedUsers(): EmbedsMany
    {
        return $this->embedsMany(AuthorizedUser::class);
    }

    /**
     * @return EmbedsMany<ActivityLog, Transaction, mixed>
     */
    public function activityLogs(): EmbedsMany
    {
        return $this->embedsMany(ActivityLog::class);
    }
}
