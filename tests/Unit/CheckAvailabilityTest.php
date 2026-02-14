<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use Tests\Unit\BookingData;

class CheckAvailabilityTest extends TestCase
{
    use WithFaker;

    private $bookedDates = [];

    // Exposed for tests to compute ranges
    private $b1Start;
    private $b1End;
    private $b2Start;
    private $b2End;

    protected function setUp(): void
    {
        parent::setUp();

        // Use relative future dates so tests remain valid regardless of current date
        $now = Carbon::now();

        $this->b1Start = $now->copy()->addDays(10)->format('Y-m-d');
        $this->b1End = $now->copy()->addDays(15)->format('Y-m-d');
        $this->b2Start = $now->copy()->addDays(20)->format('Y-m-d');
        $this->b2End = $now->copy()->addDays(25)->format('Y-m-d');

        // Create BookingData objects for structure (not saving to DB)
        $b1 = BookingData::make([
            'CheckInDate' => $this->b1Start . ' 00:00:00',
            'CheckOutDate' => $this->b1End . ' 00:00:00',
        ]);

        $b2 = BookingData::make([
            'CheckInDate' => $this->b2Start . ' 00:00:00',
            'CheckOutDate' => $this->b2End . ' 00:00:00',
        ]);

        $this->bookedDates = [
            ['check_in' => $this->b1Start, 'check_out' => $this->b1End],
            ['check_in' => $this->b2Start, 'check_out' => $this->b2End],
        ];
    }

    /**
     * Helper function to simulate checking room availability.
     */
    private function checkAvailability($checkInDate, $checkOutDate, $pax, $bookedDates)
    {
        // Validate blank fields
        if (empty($checkInDate) || empty($checkOutDate)) {
            return "Please fill in both Check-in and Check-out dates.";
        }

        if (empty($pax)) {
            return "Please enter number of pax.";
        }

        // Validate reversed dates
        if ($checkOutDate <= $checkInDate) {
            return "The Check-out date must be after the Check-in date.";
        }

        // Validate past dates
        if ($checkInDate < date('Y-m-d')) {
            return "Cannot book for past dates.";
        }

        // Check overlap
        foreach ($bookedDates as $booking) {
            if (
                ($checkInDate >= $booking['check_in'] && $checkInDate < $booking['check_out']) ||
                ($checkOutDate > $booking['check_in'] && $checkOutDate <= $booking['check_out']) ||
                ($checkInDate <= $booking['check_in'] && $checkOutDate >= $booking['check_out'])
            ) {
                return "Selected dates are not available.";
            }
        }

        return "Available";
    }

    /** @test */
    public function successful_checking_of_available_booking_dates()
    {
        // Pick dates strictly between the two booked ranges
        $checkIn = Carbon::parse($this->b1End)->addDay()->format('Y-m-d');
        $checkOut = Carbon::parse($this->b2Start)->subDay()->format('Y-m-d');

        $result = $this->checkAvailability($checkIn, $checkOut, 2, $this->bookedDates);
        $this->assertEquals("Available", $result);
    }

    /** @test */
    public function booking_dates_that_arent_available()
    {
        // Overlap with first booked range
        $checkIn = Carbon::parse($this->b1Start)->addDay()->format('Y-m-d');
        $checkOut = Carbon::parse($this->b1Start)->addDays(3)->format('Y-m-d');

        $result = $this->checkAvailability($checkIn, $checkOut, 2, $this->bookedDates);
        $this->assertEquals("Selected dates are not available.", $result);
    }

    /** @test */
    public function selecting_booking_dates_in_reversed_order()
    {
        $checkIn = Carbon::parse($this->b1End)->addDays(3)->format('Y-m-d');
        $checkOut = Carbon::parse($this->b1End)->addDay()->format('Y-m-d');
        $result = $this->checkAvailability($checkIn, $checkOut, 2, $this->bookedDates);
        $this->assertEquals("The Check-out date must be after the Check-in date.", $result);
    }

    /** @test */
    public function select_booking_dates_but_blank_pax()
    {
        $checkIn = Carbon::parse($this->b1End)->addDay()->format('Y-m-d');
        $checkOut = Carbon::parse($this->b1End)->addDays(3)->format('Y-m-d');
        $result = $this->checkAvailability($checkIn, $checkOut, '', $this->bookedDates);
        $this->assertEquals("Please enter number of pax.", $result);
    }

    /** @test */
    public function input_pax_but_blank_dates()
    {
        $result = $this->checkAvailability('', '', 3, $this->bookedDates);
        $this->assertEquals("Please fill in both Check-in and Check-out dates.", $result);
    }

    /** @test */
    public function choose_booking_dates_in_the_past()
    {
        $pastDate = Carbon::now()->subDays(10)->format('Y-m-d');
        $pastOut = Carbon::now()->subDays(5)->format('Y-m-d');
        $result = $this->checkAvailability($pastDate, $pastOut, 2, $this->bookedDates);
        $this->assertEquals("Cannot book for past dates.", $result);
    }
}
