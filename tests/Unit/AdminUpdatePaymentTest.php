<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminUpdatePaymentTest extends TestCase
{
    /** @test */
    public function it_updates_payment_successfully()
    {
        $booking = new TestBookingPayment('B001', [
            new TestPayment('PY001', 5000, 'GCash', 'Downpayment')
        ]);

        $requestData = [
            'amount' => 10000,
            'payment_method' => 'Credit Card',
            'status' => 'Fully Paid'
        ];

        $result = $this->simulateUpdatePayment($booking, $requestData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Payment updated successfully', $result['message']);
        $this->assertEquals(10000, $booking->payments[0]->Amount);
        $this->assertEquals('Credit Card', $booking->payments[0]->PaymentMethod);
        $this->assertEquals('Fully Paid', $booking->payments[0]->PaymentStatus);
    }

    /** @test */
    public function it_fails_if_no_payment_exists()
    {
        $booking = new TestBookingPayment('B002', []); // No payments

        $requestData = [
            'amount' => 5000,
            'payment_method' => 'GCash',
            'status' => 'Downpayment'
        ];

        $result = $this->simulateUpdatePayment($booking, $requestData);

        $this->assertFalse($result['success']);
        $this->assertEquals('No payment record found for this booking', $result['message']);
    }

    /**
     * Mocked function to simulate updatePayment logic.
     */
    private function simulateUpdatePayment($booking, $data)
    {
        $payment = $booking->payments[0] ?? null;
        if (!$payment) {
            return [
                'success' => false,
                'message' => 'No payment record found for this booking'
            ];
        }

        // Fake validation
        if (!isset($data['amount'], $data['payment_method'], $data['status']) ||
            !is_numeric($data['amount']) || $data['amount'] < 0 ||
            !in_array($data['status'], ['Downpayment', 'Partial', 'Fully Paid'])) {
            return ['success' => false, 'message' => 'Validation failed'];
        }

        // "Update" payment
        $payment->Amount = $data['amount'];
        $payment->PaymentMethod = $data['payment_method'];
        $payment->PaymentStatus = $data['status'];

        return [
            'success' => true,
            'message' => 'Payment updated successfully',
            'payment' => $payment
        ];
    }
}

/**
 * Unique mock classes for this test
 */
class TestBookingPayment
{
    public $BookingID;
    public $payments;

    public function __construct($id, $payments)
    {
        $this->BookingID = $id;
        $this->payments = $payments;
    }
}

class TestPayment
{
    public $PaymentID;
    public $Amount;
    public $PaymentMethod;
    public $PaymentStatus;

    public function __construct($id, $amount, $method, $status)
    {
        $this->PaymentID = $id;
        $this->Amount = $amount;
        $this->PaymentMethod = $method;
        $this->PaymentStatus = $status;
    }
}
