<?php

// Load the Laravel application
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Checking database structure:\n\n";
    
    // Check guests table
    $guestColumns = \Illuminate\Support\Facades\Schema::getColumnListing('guests');
    echo "Guests table columns: " . implode(', ', $guestColumns) . "\n";
    
    // Check packages table
    $packageColumns = \Illuminate\Support\Facades\Schema::getColumnListing('packages');
    echo "Packages table columns: " . implode(', ', $packageColumns) . "\n";
    
    // Check payments table
    $paymentColumns = \Illuminate\Support\Facades\Schema::getColumnListing('payments');
    echo "Payments table columns: " . implode(', ', $paymentColumns) . "\n";
    
    // Check if bookings table exists
    if (\Illuminate\Support\Facades\Schema::hasTable('bookings')) {
        $bookingColumns = \Illuminate\Support\Facades\Schema::getColumnListing('bookings');
        echo "Bookings table columns: " . implode(', ', $bookingColumns) . "\n";
    } else {
        echo "Bookings table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}