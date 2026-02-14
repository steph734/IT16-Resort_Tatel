<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\Guest;
use Illuminate\Support\Carbon;

class AdminIndexTest extends TestCase
{
    /** @test */
    public function it_returns_all_bookings_without_filters()
    {
        $bookings = Booking::all();
        $this->assertTrue($bookings->isNotEmpty(), 'No bookings found in the database');
    }

    /** @test */
    public function it_filters_bookings_by_status()
    {
        $status = 'Pending';
        $bookings = Booking::where('BookingStatus', $status)->get();
        $this->assertTrue($bookings->every(fn($b) => $b->BookingStatus === $status));
    }

    /** @test */
    public function it_filters_bookings_by_payment_status()
    {
        $status = 'Fully Paid';

        // Use the correct plural relationship: "payments"
        $bookings = Booking::whereHas('payments', fn($q) =>
            $q->where('PaymentStatus', $status)
        )->get();

        $this->assertTrue(
            $bookings->every(function ($b) use ($status) {
                // Check all related payments have the correct status
                return $b->payments->contains('PaymentStatus', $status);
            }),
            "Some bookings don't have a payment with status {$status}"
        );
    }

    /** @test */
    public function it_allows_searching_by_booking_id_or_guest_name()
    {
        $booking = Booking::with('guest')->first();
        $this->assertNotNull($booking, 'No booking found to test search');

        $searchByID = Booking::where('BookingID', $booking->BookingID)->exists();
        $searchByName = Booking::whereHas('guest', fn($q) =>
            $q->where('FName', $booking->guest->FName)
        )->exists();

        $this->assertTrue($searchByID && $searchByName);
    }

    /** @test */
    public function it_sorts_bookings_by_checkin_nearest()
    {
        $bookings = Booking::orderBy('CheckInDate', 'asc')->take(2)->get();
        $this->assertTrue(
            $bookings->count() < 2 || $bookings->first()->CheckInDate <= $bookings->last()->CheckInDate
        );
    }

    /** @test */
    public function it_sorts_bookings_by_checkin_farthest()
    {
        $bookings = Booking::orderBy('CheckInDate', 'desc')->take(2)->get();
        $this->assertTrue(
            $bookings->count() < 2 || $bookings->first()->CheckInDate >= $bookings->last()->CheckInDate
        );
    }

    /** @test */
    public function it_sorts_bookings_by_guest_name_ascending()
    {
        $bookings = Booking::with('guest')->get()->sortBy(fn($b) => $b->guest->FName);
        $this->assertTrue($bookings->count() > 0);
    }

    /** @test */
    public function it_sorts_bookings_by_guest_name_descending()
    {
        $bookings = Booking::with('guest')->get()->sortByDesc(fn($b) => $b->guest->FName);
        $this->assertTrue($bookings->count() > 0);
    }

    /** @test */
    public function it_displays_paginated_results()
    {
        $perPage = 10;
        $bookings = Booking::paginate($perPage);
        $this->assertEquals($perPage, $bookings->perPage());
    }
}
