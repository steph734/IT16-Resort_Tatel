<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Booking;

echo "Verifying booking order (Staying → Booked → Completed, newest first):\n";
echo str_repeat("=", 80) . "\n\n";

$bookings = Booking::orderByRaw("CASE 
    WHEN BookingStatus = 'Staying' THEN 1
    WHEN BookingStatus = 'Booked' THEN 2
    WHEN BookingStatus = 'Completed' THEN 3
    WHEN BookingStatus = 'Cancelled' THEN 4
    ELSE 5 END")
    ->orderBy('CheckInDate', 'desc')
    ->take(15)
    ->get(['BookingID', 'BookingStatus', 'CheckInDate']);

foreach ($bookings as $booking) {
    printf("%-10s | %-15s | %s\n", 
        $booking->BookingID, 
        $booking->BookingStatus, 
        $booking->CheckInDate
    );
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Total bookings in database: " . Booking::count() . "\n";
echo "November bookings: " . Booking::whereMonth('CheckInDate', 11)->count() . "\n";
echo "Date range: " . Booking::min('CheckInDate') . " to " . Booking::max('CheckInDate') . "\n";
