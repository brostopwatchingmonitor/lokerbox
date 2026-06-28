<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Laravel\Passkeys\Passkey as BasePasskey;
use MongoDB\Laravel\Eloquent\DocumentModel;

/**
 * @property string $_id
 * @property string $id
 * @property string $user_id
 * @property string $name
 * @property string $credential_id
 * @property array<string, mixed> $credential
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $authenticator
 */
class Passkey extends BasePasskey
{
    use DocumentModel;

    protected $connection = 'mongodb';

    protected string $collection = 'passkeys';
}
