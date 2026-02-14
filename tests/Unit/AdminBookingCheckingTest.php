<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Carbon\Carbon;

class AdminBookingCheckingTest extends TestCase
{
    private $mockBookings;
    private $mockClosedDates;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock bookings
        $this->mockBookings = [
            new MockBookingCheck('B001', 'Confirmed', '2025-10-10', '2025-10-12'),
            new MockBookingCheck('B002', 'Pending', '2025-10-15', '2025-10-18'),
            new MockBookingCheck('B003', 'Cancelled', '2025-10-20', '2025-10-22'),
        ];

        // Mock closed dates
        $this->mockClosedDates = [
            '2025-10-25',
            '2025-10-26'
        ];
    }

    /** @test */
    public function it_detects_conflicting_dates()
    {
        $result = $this->simulateCheckDateConflict('2025-10-11', '2025-10-13');
        $this->assertTrue($result['conflict']);
        $this->assertEquals('These dates conflict with existing bookings', $result['message']);
        $this->assertCount(1, $result['conflicting_bookings']);
        $this->assertEquals('B001', $result['conflicting_bookings'][0]['BookingID']);
    }

    /** @test */
    public function it_allows_non_conflicting_dates()
    {
        $result = $this->simulateCheckDateConflict('2025-10-13', '2025-10-14');
        $this->assertFalse($result['conflict']);
        $this->assertEquals('Dates are available', $result['message']);
    }

    /** @test */
    public function it_returns_only_non_cancelled_booked_dates()
    {
        $bookedDates = $this->simulateGetBookedDates();
        $this->assertCount(2, $bookedDates);
        $this->assertEquals('2025-10-10', $bookedDates[0]['start']);
        $this->assertEquals('2025-10-11', $bookedDates[0]['end']); // checkout -1
    }

    /** @test */
    public function it_returns_closed_dates_correctly()
    {
        $closedDates = $this->simulateGetClosedDates();
        $this->assertEquals(['2025-10-25', '2025-10-26'], $closedDates);
    }

    // -------------------- Simulation Methods --------------------

    private function simulateCheckDateConflict($checkIn, $checkOut, $excludeBookingId = null)
    {
        $conflicts = array_filter($this->mockBookings, function ($b) use ($checkIn, $checkOut, $excludeBookingId) {
            if ($b->BookingStatus === 'Cancelled') return false;
            if ($excludeBookingId && $b->BookingID === $excludeBookingId) return false;

            $start = new \DateTime($b->CheckInDate);
            $end = new \DateTime($b->CheckOutDate);
            $checkStart = new \DateTime($checkIn);
            $checkEnd = new \DateTime($checkOut);

            return ($checkStart <= $end && $checkEnd >= $start);
        });

        if (count($conflicts) > 0) {
            return [
                'conflict' => true,
                'message' => 'These dates conflict with existing bookings',
                'conflicting_bookings' => array_map(fn($b) => [
                    'BookingID' => $b->BookingID,
                    'CheckInDate' => $b->CheckInDate,
                    'CheckOutDate' => $b->CheckOutDate
                ], $conflicts)
            ];
        }

        return [
            'conflict' => false,
            'message' => 'Dates are available'
        ];
    }

    private function simulateGetBookedDates()
    {
        $booked = array_filter($this->mockBookings, fn($b) => $b->BookingStatus !== 'Cancelled');
        return array_map(function ($b) {
            $end = (new \DateTime($b->CheckOutDate))->modify('-1 day');
            return [
                'start' => $b->CheckInDate,
                'end' => $end->format('Y-m-d'),
            ];
        }, $booked);
    }

    private function simulateGetClosedDates()
    {
        return $this->mockClosedDates;
    }
}

// -------------------- Mock Classes --------------------
class MockBookingCheck
{
    public $BookingID, $BookingStatus, $CheckInDate, $CheckOutDate;

    public function __construct($id, $status, $checkIn, $checkOut)
    {
        $this->BookingID = $id;
        $this->BookingStatus = $status;
        $this->CheckInDate = $checkIn;
        $this->CheckOutDate = $checkOut;
    }
}
