<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the Dart Set rental that was marked as Damaged
$rental = \App\Models\Rental::where('BookingID', 'B003')
    ->where('status', 'Damaged')
    ->first();

if ($rental) {
    echo "Found rental: {$rental->rentalItem->name} (ID: {$rental->id})\n";
    echo "Current status: {$rental->status}\n";
    
    // Reset to Issued so you can return it again with the fee
    $rental->status = 'Issued';
    $rental->returned_at = null;
    $rental->returned_by = null;
    $rental->condition = null;
    $rental->save();
    
    echo "✓ Rental reset to 'Issued' status\n";
    echo "Now you can return it again through the UI with the damage fee!\n";
} else {
    echo "No damaged Dart Set rental found for booking B003\n";
    
    // Show all rentals for this booking
    $allRentals = \App\Models\Rental::where('BookingID', 'B003')->get();
    echo "\nAll rentals for B003:\n";
    foreach ($allRentals as $r) {
        echo "- {$r->rentalItem->name}: {$r->status}\n";
    }
}
