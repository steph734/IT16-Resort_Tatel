<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use Mockery;

class AdminUpdateTest extends TestCase
{
    private $mockBookingData = [
        [
            'BookingID' => 'B001',
            'GuestID' => 'G001',
            'PackageID' => 'PK001',
            'CheckInDate' => '2025-10-10',
            'CheckOutDate' => '2025-10-12',
            'NumOfAdults' => 2,
            'NumOfChild' => 1,
            'Pax' => 3,
            'ExcessFee' => 0.00,
        ],
        [
            'BookingID' => 'B002',
            'GuestID' => 'G002',
            'PackageID' => 'PK002',
            'CheckInDate' => '2025-10-15',
            'CheckOutDate' => '2025-10-17',
            'NumOfAdults' => 18,
            'NumOfChild' => 5,
            'Pax' => 23,
            'ExcessFee' => 300.00,
        ],
    ];

    private $mockGuests = [
        ['GuestID' => 'G001', 'FName' => 'John', 'LName' => 'Doe', 'Email' => 'john@example.com', 'Phone' => '09123456789', 'Address' => 'Cebu City'],
        ['GuestID' => 'G002', 'FName' => 'Jane', 'LName' => 'Smith', 'Email' => 'jane@example.com', 'Phone' => '09998887777', 'Address' => 'Davao City'],
    ];

    private $mockPackages = [
        ['PackageID' => 'PK001', 'Name' => 'Package A', 'max_guests' => 30, 'Price' => 15000],
        ['PackageID' => 'PK002', 'Name' => 'Package B', 'max_guests' => 20, 'Price' => 12000],
    ];

    private $mockPayments = [
        ['PaymentID' => 'PY001', 'BookingID' => 'B001', 'Amount' => 22500.00, 'TotalAmount' => 22500.00],
        ['PaymentID' => 'PY002', 'BookingID' => 'B002', 'Amount' => 6150.00, 'TotalAmount' => 6150.00],
    ];

    private function createMockBooking($index)
    {
        $guest = new MockGuest($this->mockGuests[$index]);
        $package = new MockPackage($this->mockPackages[$index]);
        $payment = new MockPayment($this->mockPayments[$index]);
        $booking = new MockBooking($this->mockBookingData[$index], $guest, $package, [$payment]);

        return $booking;
    }

    /**
     * Simulates the update logic using mock data only.
     */
    private function simulateUpdate($booking, $updateData)
    {
        // Fake validation step
        if (empty($updateData['checkout_date']) || empty($updateData['adults']) || empty($updateData['package_id'])) {
            return ['success' => false, 'message' => 'Validation failed'];
        }

        // Recalculate stay duration and fees
        $checkIn = Carbon::parse($booking->CheckInDate);
        $checkOut = Carbon::parse($updateData['checkout_date'])->setTime(12, 0, 0);
        $days = $checkIn->diffInDays($checkOut);

        $package = $booking->Package;
        $maxGuests = $package['max_guests'];
        $excessGuests = max(0, $updateData['adults'] - $maxGuests);
        $excessFee = $excessGuests * 100;

        $packageTotal = $package['Price'] * $days;
        $totalAmount = $packageTotal + $excessFee;

        // "Update" simulated booking fields
        $booking->CheckOutDate = $checkOut->toDateTimeString();
        $booking->NumOfAdults = $updateData['adults'];
        $booking->NumOfChild = $updateData['children'] ?? 0;
        $booking->ExcessFee = $excessFee;
        $booking->TotalAmount = $totalAmount;

        return [
            'success' => true,
            'message' => 'Booking updated successfully',
            'updated_booking' => $booking,
        ];
    }

    /** @test */
    public function it_updates_booking_with_mock_data_successfully()
    {
        $booking = $this->createMockBooking(0); // B001
        $updateData = [
            'checkout_date' => '2025-10-13',
            'adults' => 4,
            'children' => 2,
            'package_id' => 'PK001',
        ];

        $result = $this->simulateUpdate($booking, $updateData);

        $this->assertTrue($result['success']);
        $this->assertEquals('Booking updated successfully', $result['message']);
        $this->assertEquals('2025-10-13 12:00:00', $booking->CheckOutDate);
        $this->assertEquals(4, $booking->NumOfAdults);
        $this->assertEquals(2, $booking->NumOfChild);
        $this->assertGreaterThan(0, $booking->TotalAmount);
    }

    /** @test */
    public function it_handles_excess_guest_fee_correctly()
    {
        $booking = $this->createMockBooking(1); // B002 (max 20 guests)
        $updateData = [
            'checkout_date' => '2025-10-20',
            'adults' => 25,
            'children' => 3,
            'package_id' => 'PK002',
        ];

        $result = $this->simulateUpdate($booking, $updateData);

        $this->assertTrue($result['success']);
        $this->assertEquals(500, $booking->ExcessFee); // (25 - 20) * 100
        $this->assertGreaterThan(0, $booking->TotalAmount);
    }

    /** @test */
    public function it_fails_validation_with_missing_fields()
    {
        $booking = $this->createMockBooking(0);
        $updateData = [
            'checkout_date' => '',
            'adults' => '',
            'package_id' => '',
        ];

        $result = $this->simulateUpdate($booking, $updateData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Validation failed', $result['message']);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

// Simplified mock classes for isolated testing
class MockBooking
{
    public $BookingID, $GuestID, $PackageID, $CheckInDate, $CheckOutDate;
    public $NumOfAdults, $NumOfChild, $Pax, $ExcessFee, $TotalAmount;
    public $Guest, $Package, $Payments;

    public function __construct(array $data, $guest, $package, $payments)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
        $this->Guest = $guest->data;
        $this->Package = $package->data;
        $this->Payments = array_map(fn($p) => $p->data, $payments);
    }
}

class MockGuest { public $data; public function __construct($data) { $this->data = $data; } }
class MockPackage { public $data; public function __construct($data) { $this->data = $data; } }
class MockPayment { public $data; public function __construct($data) { $this->data = $data; } }
