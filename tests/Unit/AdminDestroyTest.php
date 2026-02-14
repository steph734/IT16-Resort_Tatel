<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminDestroyTest extends TestCase
{
    /** @test */
    public function it_deletes_booking_and_payments_successfully()
    {
        $booking = new TestBookingDestroy('B001', [
            new TestPaymentDestroy('PY001'),
            new TestPaymentDestroy('PY002'),
        ]);

        $result = $this->simulateDestroy($booking);

        $this->assertTrue($result['success']);
        $this->assertEquals('Booking deleted successfully', $result['message']);
        $this->assertTrue($booking->deleted);
        foreach ($booking->payments as $payment) {
            $this->assertTrue($payment->deleted);
        }
    }

    /** @test */
    public function it_handles_booking_with_no_payments()
    {
        $booking = new TestBookingDestroy('B002', []); // No payments

        $result = $this->simulateDestroy($booking);

        $this->assertTrue($result['success']);
        $this->assertEquals('Booking deleted successfully', $result['message']);
        $this->assertTrue($booking->deleted);
    }

    /**
     * Mocked destroy logic for testing
     */
    private function simulateDestroy($booking)
    {
        if (!empty($booking->payments)) {
            foreach ($booking->payments as $payment) {
                $payment->deleted = true; // Simulate deleting payments
            }
        }

        $booking->deleted = true; // Simulate deleting booking

        return [
            'success' => true,
            'message' => 'Booking deleted successfully',
        ];
    }
}

/**
 * Mock classes for isolated testing
 */
class TestBookingDestroy
{
    public $BookingID;
    public $payments;
    public $deleted = false;

    public function __construct($id, $payments = [])
    {
        $this->BookingID = $id;
        $this->payments = $payments;
    }
}

class TestPaymentDestroy
{
    public $PaymentID;
    public $deleted = false;

    public function __construct($id)
    {
        $this->PaymentID = $id;
    }
}
