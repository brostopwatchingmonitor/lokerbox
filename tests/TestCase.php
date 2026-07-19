<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Wipe all MongoDB collections to ensure test isolation
        try {
            $mongo = \Illuminate\Support\Facades\DB::connection('mongodb');
            foreach ($mongo->getMongoDB()->listCollections() as $collectionInfo) {
                $collectionName = $collectionInfo->getName();
                $mongo->getCollection($collectionName)->deleteMany([]);
            }
        } catch (\Exception $e) {
            // Ignore if connection or database is not initialized yet
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
