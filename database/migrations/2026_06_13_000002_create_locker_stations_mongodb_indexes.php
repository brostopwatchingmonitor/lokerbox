<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->table('locker_stations', function (Blueprint $collection) {
            // Indeks geospatial 2dsphere untuk pencarian stasiun terdekat (radius/jarak)
            $collection->geospatial('location_geom', '2dsphere');

            // Indeks komposit untuk ketersediaan tipe box tertentu di stasiun online
            $collection->index([
                'connectivity_status' => 1,
                'boxes.size_type' => 1,
                'boxes.is_available' => 1,
            ], 'station_box_availability_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->table('locker_stations', function (Blueprint $collection) {
            $collection->dropIndex('location_geom_2dsphere');
            $collection->dropIndex('station_box_availability_index');
        });
    }
};
