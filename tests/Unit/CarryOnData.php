<?php

namespace Tests\Unit;

use Carbon\Carbon;

/**
 * CarryOnData
 *
 * Small helper to provide session-shaped booking and guest payloads used across
 * the booking -> payment -> confirmation flows in unit tests.
 */
class CarryOnData
{
    public static function guest(array $overrides = []): array
    {
        $data = [
            'FName' => 'Test',
            'MName' => 'T',
            'LName' => 'User',
            'Email' => 'test.user@example.com',
            'Phone' => '09171234567',
            'Address' => '123 Test St',
            'SocialMedia' => '@testuser',
            'Contactable' => true,
        ];

        return array_replace($data, $overrides);
    }

    public static function booking(array $overrides = []): array
    {
        $checkIn = Carbon::now()->addDays(20)->format('Y-m-d');
        $checkOut = Carbon::now()->addDays(23)->format('Y-m-d');

        $data = [
            'PackageID' => 'PK001',
            'CheckInDate' => $checkIn,
            'CheckOutDate' => $checkOut,
            'Pax' => 4,
            'NumOfAdults' => 2,
            'NumOfChild' => 2,
            'NumOfSeniors' => 0,
            'TotalAmount' => 20000.00,
            'ExcessFee' => 0,
        ];

        return array_replace($data, $overrides);
    }

    /**
     * bookingDetails shaped like the session payload used by BookingController
     * ['guest' => [...], 'booking' => [...]]
     */
    public static function bookingDetails(array $overrides = []): array
    {
        $guest = self::guest($overrides['guest'] ?? []);
        $booking = self::booking($overrides['booking'] ?? []);

        $base = [
            'guest' => $guest,
            'booking' => $booking,
        ];

        // Allow shallow merging of nested overrides
        if (!empty($overrides)) {
            if (isset($overrides['guest'])) {
                $base['guest'] = array_replace($base['guest'], $overrides['guest']);
            }
            if (isset($overrides['booking'])) {
                $base['booking'] = array_replace($base['booking'], $overrides['booking']);
            }
        }

        return $base;
    }

    /**
     * booking_data used in payment view (flattened payload)
     */
    public static function bookingData(array $overrides = []): array
    {
        $booking = self::booking($overrides['booking'] ?? []);

        $packagePrice = $overrides['package_price'] ?? 10000;
        $checkIn = Carbon::parse($booking['CheckInDate']);
        $checkOut = Carbon::parse($booking['CheckOutDate']);
        $daysOfStay = max(1, $checkOut->diffInDays($checkIn));

        $packageTotal = $packagePrice * $daysOfStay;
        $excessFee = $booking['ExcessFee'] ?? 0;
        $totalAmount = $booking['TotalAmount'] ?? ($packageTotal + $excessFee);

        $today = Carbon::today();
        $daysUntilCheckIn = max(0, $today->diffInDays($checkIn, false));
        $isReservationFeeEligible = $daysUntilCheckIn >= 14;

        $reservationFee = 1000;
        $downpaymentAmount = $isReservationFeeEligible ? $reservationFee : ($totalAmount * 0.5);

        $data = [
            'total_amount' => $totalAmount,
            'downpayment_amount' => $downpaymentAmount,
            'package_total' => $packageTotal,
            'excess_fee' => $excessFee,
            'package_id' => $booking['PackageID'],
            'package_name' => ($overrides['package_name'] ?? 'Sample Package') . ' - ₱' . number_format($packagePrice, 0) . '/day',
            'check_in' => $booking['CheckInDate'],
            'check_out' => $booking['CheckOutDate'],
            'num_of_adults' => $booking['NumOfAdults'],
            'num_of_child' => $booking['NumOfChild'],
            'num_of_seniors' => $booking['NumOfSeniors'],
            'total_guests' => $booking['Pax'],
            'days_of_stay' => $daysOfStay,
            'max_guests' => $overrides['max_guests'] ?? 4,
            'excess_guests' => max(0, $booking['NumOfAdults'] - ($overrides['max_guests'] ?? 4)),
            'days_until_checkin' => $daysUntilCheckIn,
            'is_reservation_fee_eligible' => $isReservationFeeEligible,
            'reservation_fee' => $reservationFee,
            'payment_label' => $isReservationFeeEligible ? 'Reservation Fee' : 'Required Downpayment (50%)',
        ];

        return array_replace($data, $overrides['booking_data'] ?? []);
    }

    /**
     * Simulate the paymongo_checkout session data stored for deferred commit flow
     */
    public static function paymongoCheckout(array $overrides = []): array
    {
        $booking = self::booking($overrides['booking'] ?? []);
        $guest = self::guest($overrides['guest'] ?? []);

        $data = [
            'id' => $overrides['id'] ?? 'pm_ck_' . uniqid(),
            'url' => $overrides['url'] ?? 'https://paymongo.test/checkout/' . uniqid(),
            'purpose' => $overrides['purpose'] ?? 'Downpayment',
            'amount' => $overrides['amount'] ?? ($booking['TotalAmount'] ?? 0),
            'booking' => $booking,
            'guest' => $guest,
            'created_at' => now()->toDateTimeString(),
        ];

        return array_replace($data, $overrides);
    }

    /**
     * Simulate the paymongo_checkout_intent used by confirmNow() deferred flow
     */
    public static function paymongoIntent(array $overrides = []): array
    {
        $booking = self::booking($overrides['booking'] ?? []);
        $guest = self::guest($overrides['guest'] ?? []);

        $data = [
            'purpose' => $overrides['purpose'] ?? 'Downpayment',
            'amount' => $overrides['amount'] ?? ($booking['TotalAmount'] ?? 0),
            'booking' => $booking,
            'guest' => $guest,
            'created_at' => now()->toDateTimeString(),
        ];

        return array_replace($data, $overrides);
    }
}
