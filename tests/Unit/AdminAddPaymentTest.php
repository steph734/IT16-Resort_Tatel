<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class AdminAddPaymentTest extends TestCase
{
    private $mockBooking;
    private $mockPayments;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock existing payments
        $this->mockPayments = [
            new MockPaymentA('PY001', 5000, 'Downpayment', 'GCash', '2025-10-09'),
        ];

        // Mock booking
        $this->mockBooking = new MockBookingA(
            'B001',
            'Confirmed',
            '2025-10-09',
            '2025-10-09',
            '2025-10-12',
            3,
            0,
            3,
            1000, // ExcessFee
            new MockPackageA('PK001', 'Package A', 15000, 30),
            $this->mockPayments
        );
    }

    /** @test */
    public function it_adds_payment_as_downpayment()
    {
        $newPayment = $this->simulateAddPayment($this->mockBooking, 5000, 'Cash');

        $this->assertEquals('Downpayment', $newPayment->PaymentStatus);
        $this->assertCount(2, $this->mockBooking->payments);
        $this->assertEquals(5000, $newPayment->Amount);
    }

    /** @test */
    public function it_adds_payment_as_fully_paid()
    {
        $newPayment = $this->simulateAddPayment($this->mockBooking, 50000, 'GCash');

        $this->assertEquals('Fully Paid', $newPayment->PaymentStatus);
    }

    // -------------------- Simulation method --------------------
    private function simulateAddPayment($booking, $amount, $method, $purpose = null, $reference = null)
    {
        $checkIn = new \DateTime($booking->CheckInDate);
        $checkOut = new \DateTime($booking->CheckOutDate);
        $days = $checkIn->diff($checkOut)->days;
        $totalPrice = ($booking->package->Price ?? 0) * $days;

        $excessFee = $booking->ExcessFee ?? 0;
        $totalAmount = $totalPrice + $excessFee;

        $previousTotal = array_sum(array_map(fn($p) => $p->Amount, $booking->payments));
        $newTotal = $previousTotal + $amount;

        $halfAmount = $totalAmount * 0.5;
        $paymentStatus = 'Downpayment';
        if ($newTotal >= $totalAmount) {
            $paymentStatus = 'Fully Paid';
        } elseif ($newTotal > $halfAmount) {
            $paymentStatus = 'Partial';
        }

        $payment = new MockPaymentA(
            'PY' . str_pad(count($booking->payments) + 1, 3, '0', STR_PAD_LEFT),
            $amount,
            $paymentStatus,
            $method,
            Carbon::now()->format('Y-m-d')
        );

        $booking->payments[] = $payment;

        return $payment;
    }
}

// -------------------- Mock Classes --------------------
class MockBookingA
{
    public $BookingID, $BookingStatus, $BookingDate, $CheckInDate, $CheckOutDate;
    public $NumOfAdults, $NumOfChild, $Pax, $ExcessFee;
    public $package;
    public $payments;

    public function __construct($id, $status, $bookingDate, $checkIn, $checkOut, $adults, $children, $pax, $excessFee, $package, $payments)
    {
        $this->BookingID = $id;
        $this->BookingStatus = $status;
        $this->BookingDate = $bookingDate;
        $this->CheckInDate = $checkIn;
        $this->CheckOutDate = $checkOut;
        $this->NumOfAdults = $adults;
        $this->NumOfChild = $children;
        $this->Pax = $pax;
        $this->ExcessFee = $excessFee;
        $this->package = $package;
        $this->payments = $payments;
    }
}

class MockPackageA
{
    public $PackageID, $Name, $Price, $max_guests;

    public function __construct($id, $name, $price, $max_guests)
    {
        $this->PackageID = $id;
        $this->Name = $name;
        $this->Price = $price;
        $this->max_guests = $max_guests;
    }
}

class MockPaymentA
{
    public $PaymentID, $Amount, $PaymentStatus, $PaymentMethod, $PaymentDate;

    public function __construct($id, $amount, $status, $method, $date)
    {
        $this->PaymentID = $id;
        $this->Amount = $amount;
        $this->PaymentStatus = $status;
        $this->PaymentMethod = $method;
        $this->PaymentDate = $date;
    }
}
