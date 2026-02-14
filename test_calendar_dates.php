<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Booking;
use Carbon\Carbon;

echo "=== Testing Calendar Date Ranges ===\n\n";

$bookedDates = Booking::select('CheckInDate', 'CheckOutDate', 'BookingStatus')
    ->whereIn('BookingStatus', ['Pending', 'Booked', 'Confirmed', 'Staying'])
    ->get()
    ->map(function ($booking) {
        $checkIn = Carbon::parse($booking->CheckInDate)->format('Y-m-d');
        $checkOut = Carbon::parse($booking->CheckOutDate)->format('Y-m-d');
        $endBlocked = Carbon::parse($booking->CheckOutDate)->subDay()->format('Y-m-d');
        return [
            'booking_status' => $booking->BookingStatus,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'start' => $checkIn,
            'end' => $endBlocked
        ];
    })
    ->values()
    ->all();

echo "Booked Date Ranges:\n";
echo json_encode($bookedDates, JSON_PRETTY_PRINT);

echo "\n\nTest Case: B005 (Dec 1-2, Staying)\n";
echo "- Check-in: 2025-12-01\n";
echo "- Check-out: 2025-12-02\n";
echo "- Blocked range: 2025-12-01 to 2025-12-01 (Dec 2 is available!)\n";

echo "\nTest Case: B006 (Dec 16-18, Booked)\n";
echo "- Check-in: 2025-12-16\n";
echo "- Check-out: 2025-12-18\n";
echo "- Blocked range: 2025-12-16 to 2025-12-17 (Dec 18 is available!)\n";
