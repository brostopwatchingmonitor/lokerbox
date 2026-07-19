<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Services\LockerRentalService;
use App\Services\Payment\MidtransServiceInterface;
use Tests\TestCase;

class LockerDurationTest extends TestCase
{
    /**
     * Test that ordering a locker with custom duration saves the duration
     * and sets the due_at timestamp correctly when activated.
     */
    public function test_custom_duration_is_saved_and_applied(): void
    {
        // Mock Midtrans service responses
        $this->mock(MidtransServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createSnapTransaction')->andReturn([
                'success' => true,
                'token' => 'dummy-token',
                'isMock' => true,
            ]);
            $mock->shouldReceive('verifySignature')->andReturn(true);
            $mock->shouldReceive('mapTransactionStatus')->andReturn([
                'payment_status' => 'SUCCESS',
                'locker_status' => 'ACTIVE',
                'is_paid' => true,
            ]);
        });

        $user = User::factory()->create();

        // 1. Create order with duration = 4 hours
        $lockerSize = 'Small';
        $duration = 4;
        $price = 5000.00;

        $service = app(LockerRentalService::class);
        $result = $service->createRentalOrder($user, $lockerSize, $duration, $price);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['orderId']);

        // Check stored duration in database
        $transaction = Transaction::where('payments.gateway_ref', $result['orderId'])->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($duration, $transaction->fees['duration']);

        // 2. Simulate webhook payment notification callback
        $webhookPayload = [
            'order_id' => $result['orderId'],
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
        ];

        $webhookResult = $service->handlePaymentWebhook($webhookPayload);
        $this->assertTrue($webhookResult);

        // Refresh transaction and check due_at timestamp
        $transaction->refresh();
        $this->assertEquals('ACTIVE', $transaction->status);

        $startedAt = \Illuminate\Support\Carbon::parse($transaction->getAttribute('timestamps')['started_at']);
        $dueAt = \Illuminate\Support\Carbon::parse($transaction->getAttribute('timestamps')['due_at']);

        // The difference in hours should be exactly the requested duration (4 hours)
        $this->assertEquals($duration, $startedAt->diffInHours($dueAt));
    }
}
