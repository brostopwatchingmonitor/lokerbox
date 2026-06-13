<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->table('users', function (Blueprint $collection) {
            $collection->unique('email');
            $collection->unique('account_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->table('users', function (Blueprint $collection) {
            $collection->dropUnique(['email']);
            $collection->dropUnique(['account_code']);
        });
    }
};
