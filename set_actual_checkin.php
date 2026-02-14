<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find booking B001 and set actual check-in time
$booking = App\Models\Booking::where('BookingID', 'B001')->first();

if ($booking) {
    $booking->ActualCheckInTime = now();
    $booking->save();
    echo "Updated booking {$booking->BookingID} with actual check-in time: {$booking->ActualCheckInTime}\n";
} else {
    echo "Booking B001 not found\n";
}
