<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

// Test the closed date validation logic
use App\Models\ClosedDate;
use Carbon\Carbon;

echo "Testing closed date validation...\n";

// Test date range logic
$checkInDate = Carbon::parse('2025-12-01');
$checkOutDate = Carbon::parse('2025-12-03');

echo "Check-in: " . $checkInDate->format('Y-m-d') . "\n";
echo "Check-out: " . $checkOutDate->format('Y-m-d') . "\n";

// Generate date range (excluding checkout date)
$dateRange = [];
$current = clone $checkInDate;
while ($current < $checkOutDate) {
    $dateRange[] = $current->format('Y-m-d');
    $current->modify('+1 day');
}

echo "Date range to check: " . implode(', ', $dateRange) . "\n";

// Check for closed dates
$closedDates = ClosedDate::whereIn('closed_date', $dateRange)->exists();
echo "Any closed dates in range? " . ($closedDates ? 'YES' : 'NO') . "\n";

// Test creating a closed date
echo "\nCreating a test closed date for 2025-12-01...\n";
try {
    ClosedDate::create([
        'closed_date' => '2025-12-01',
        'reason' => 'Test closure'
    ]);
    echo "Closed date created successfully.\n";
} catch (Exception $e) {
    echo "Error creating closed date: " . $e->getMessage() . "\n";
}

// Test the validation again
$closedDates = ClosedDate::whereIn('closed_date', $dateRange)->exists();
echo "Any closed dates in range now? " . ($closedDates ? 'YES' : 'NO') . "\n";

// Clean up
ClosedDate::where('closed_date', '2025-12-01')->delete();
echo "Test closed date cleaned up.\n";

echo "\nTest completed successfully!\n";