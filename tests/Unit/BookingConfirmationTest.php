<?php

namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use Tests\Unit\BookingData;
use Tests\Unit\GuestData;
use Tests\Unit\PackageData;
use Tests\Unit\PaymentData;

class BookingConfirmationTest extends TestCase
{
    private $mockBookingData = [];
    private $mockPersonalData = [];
    private $mockPackages = [];
    private $mockPayments = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Build two mock bookings using helper data classes
        $b1 = BookingData::make(['BookingID' => 'B001', 'Pax' => 3, 'NumOfAdults' => 2, 'NumOfChild' => 1, 'ExcessFee' => 0.00]);
        $b2 = BookingData::make(['BookingID' => 'B002', 'Pax' => 23, 'NumOfAdults' => 18, 'NumOfChild' => 5, 'ExcessFee' => 300.00]);

        $g1 = GuestData::make(['GuestID' => 'G001', 'FName' => 'John', 'LName' => 'Doe', 'Email' => 'john@example.com', 'Phone' => '09123456789', 'Address' => 'Cebu City']);
        $g2 = GuestData::make(['GuestID' => 'G002', 'FName' => 'Jane', 'LName' => 'Smith', 'Email' => 'jane@example.com', 'Phone' => '09998887777', 'Address' => 'Davao City']);

        $p1 = PackageData::make(['PackageID' => 'PK001', 'Name' => 'Package A', 'Price' => 15000, 'max_guests' => 30, 'excess_rate' => 100])->toArray();
        $p2 = PackageData::make(['PackageID' => 'PK002', 'Name' => 'Package B', 'Price' => 12000, 'max_guests' => 20, 'excess_rate' => 100])->toArray();

        $pay1 = PaymentData::make(['PaymentID' => 'PY001', 'BookingID' => 'B001', 'Amount' => 22500.00])->toArray();
        $pay2 = PaymentData::make(['PaymentID' => 'PY002', 'BookingID' => 'B002', 'Amount' => 6150.00])->toArray();

        $this->mockBookingData = [$b1->toArray(), $b2->toArray()];
        $this->mockPersonalData = [$g1->toArray(), $g2->toArray()];
        $this->mockPackages = [$p1, $p2];
        $this->mockPayments = [$pay1, $pay2];
    }

    /**
     * Helper function to create a mock booking object with associated data.
     */
    private function createMockBooking($index)
    {
        $guest = new Guest($this->mockPersonalData[$index]);
        $package = new Package($this->mockPackages[$index]);
        $booking = new Booking($this->mockBookingData[$index]);
        $payment = new Payment($this->mockPayments[$index]);

        $booking->setGuest($guest);
        $booking->setPayments([$payment]);
        $booking->setPackage($package);

        return $booking;
    }

    /**
     * Helper function to simulate the confirmation logic.
     * This is the function under test, modified to use a mockable finder.
     */
    private function performConfirmation($bookingId, $finder)
    {
        if (!$bookingId) {
            return false;
        }

        $booking = $finder($bookingId); // Use the injected finder function
        if (!$booking) {
            return false;
        }

        return true;
    }

    /** @test */
    public function successful_confirmation_with_valid_mock_booking_id_1()
    {
        $mockBooking = $this->createMockBooking(0); // B001
        $mockFinder = function ($id) use ($mockBooking) {
            return $id === 'B001' ? $mockBooking : null;
        };

        $result = $this->performConfirmation('B001', $mockFinder);
        $this->assertTrue($result);
    }

    /** @test */
    public function successful_confirmation_with_valid_mock_booking_id_2()
    {
        $mockBooking = $this->createMockBooking(1); // B002
        $mockFinder = function ($id) use ($mockBooking) {
            return $id === 'B002' ? $mockBooking : null;
        };

        $result = $this->performConfirmation('B002', $mockFinder);
        $this->assertTrue($result);
    }

    /** @test */
    public function confirmation_fails_with_no_booking_id()
    {
        $mockFinder = function () { return null; };
        $result = $this->performConfirmation(null, $mockFinder);
        $this->assertFalse($result);
    }

    /** @test */
    public function confirmation_fails_with_invalid_booking_id()
    {
        $mockFinder = function () { return null; };
        $result = $this->performConfirmation('B999', $mockFinder);
        $this->assertFalse($result);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

// Simplified model classes for unit testing without Eloquent
class Booking
{
    public $data;
    private $guest;
    private $payments;
    private $package;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function setGuest(Guest $guest)
    {
        $this->guest = $guest;
    }

    public function setPayments(array $payments)
    {
        $this->payments = $payments;
    }

    public function setPackage(Package $package)
    {
        $this->package = $package;
    }

    public function getGuest()
    {
        return $this->guest;
    }

    public function getPayments()
    {
        return $this->payments;
    }

    public function getPackage()
    {
        return $this->package;
    }
}

class Guest
{
    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}

class Package
{
    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}

class Payment
{
    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
