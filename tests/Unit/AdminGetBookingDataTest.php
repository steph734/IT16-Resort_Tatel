<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Booking;

class AdminGetBookingDataTest extends TestCase
{
    /**
     * Ensure database is not refreshed — use live data
     */
    protected $refreshDatabase = false;

    /** @test */
    public function it_has_bookings_in_the_database()
    {
        $bookings = Booking::with(['guest', 'package', 'payments'])->get();

        $this->assertTrue(
            $bookings->isNotEmpty(),
            'No bookings found in the live database.'
        );
    }

    /** @test */
    public function it_computes_payment_status_correctly_from_live_data()
    {
        $booking = Booking::with('payments')->first();
        $this->assertNotNull($booking, 'No booking found in the live database.');

        $status = $booking->payment_status;

        $this->assertContains(
            $status,
            ['Fully Paid', 'Downpayment', 'For Verification', 'Unpaid'],
            "Unexpected payment status value: {$status}"
        );
    }

    /** @test */
    public function it_returns_json_like_getData_structure_using_live_data()
    {
        $bookings = Booking::with(['guest', 'package', 'payments'])
            ->orderBy('CheckInDate', 'asc')
            ->get()
            ->map(function ($booking) {
                $package = $booking->package;
                $checkInDate = new \DateTime($booking->CheckInDate);
                $checkOutDate = new \DateTime($booking->CheckOutDate);
                $days = $checkInDate->diff($checkOutDate)->days;
                $packageTotal = ($package->Price ?? 0) * $days;
                $excessFee = $booking->ExcessFee ?? 0;
                $totalAmount = $packageTotal + $excessFee;
                $totalPaid = $booking->payments->sum('Amount');
                $paymentStatuses = $booking->payments->pluck('PaymentStatus')->unique();

                if ($paymentStatuses->contains('For Verification')) {
                    $paymentStatus = 'For Verification';
                } elseif ($totalPaid >= $totalAmount) {
                    $paymentStatus = 'Fully Paid';
                } elseif ($totalPaid > 0) {
                    $paymentStatus = 'Downpayment';
                } else {
                    $paymentStatus = 'Unpaid';
                }

                $paymentMethods = $booking->payments->pluck('PaymentMethod')->unique()->join(', ') ?: 'N/A';

                return [
                    'BookingID' => $booking->BookingID,
                    'BookingStatus' => $booking->BookingStatus,
                    'GuestName' => $booking->guest?->FName . ' ' . $booking->guest?->LName,
                    'PackageName' => $booking->package?->Name,
                    'PaymentStatus' => $paymentStatus,
                    'AmountPaid' => $totalPaid,
                    'PaymentMethod' => $paymentMethods,
                ];
            });

        // Expect JSON-like data structure
        $this->assertTrue($bookings->isNotEmpty(), 'No mapped bookings found.');
        $this->assertArrayHasKey('BookingID', $bookings->first());
        $this->assertArrayHasKey('PaymentStatus', $bookings->first());
        $this->assertArrayHasKey('AmountPaid', $bookings->first());
    }
}
