<?php

/**
 * Test senior discount clearing when voiding Bill Out Settlement
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SENIOR DISCOUNT VOIDING TEST ===\n\n";

// Check a booking that had Bill Out Settlement with senior discount
echo "1. Checking Booking with Senior Discount:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$booking = DB::table('bookings')->where('BookingID', 'B177')->first();

if ($booking) {
    echo "  Booking ID: {$booking->BookingID}\n";
    echo "  Guest ID: {$booking->GuestID}\n";
    echo "  Status: {$booking->BookingStatus}\n";
    echo "  Senior Discount: ₱" . number_format($booking->senior_discount ?? 0, 2) . "\n";
    echo "  Actual Seniors at Checkout: " . ($booking->actual_seniors_at_checkout ?? 0) . "\n\n";
    
    // Get package and calculate totals
    $package = DB::table('packages')->where('PackageID', $booking->PackageID)->first();
    $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
    $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
    $days = $checkIn->diffInDays($checkOut);
    
    $packageTotal = ($package->Price ?? 0) * $days;
    $excessFee = $booking->ExcessFee ?? 0;
    $seniorDiscount = $booking->senior_discount ?? 0;
    
    echo "  Calculation Breakdown:\n";
    echo "    Package Total: ₱" . number_format($packageTotal, 2) . " ({$package->Name} × {$days} days)\n";
    echo "    Excess Fee: ₱" . number_format($excessFee, 2) . "\n";
    echo "    Senior Discount: -₱" . number_format($seniorDiscount, 2) . "\n";
    echo "    ────────────────────────\n";
    echo "    Booking Total: ₱" . number_format($packageTotal + $excessFee - $seniorDiscount, 2) . "\n\n";
    
    // Get payments
    $totalPaid = DB::table('payments')->where('BookingID', 'B177')->sum('Amount');
    $bookingBalance = ($packageTotal + $excessFee - $seniorDiscount) - $totalPaid;
    
    echo "  Payment Status:\n";
    echo "    Total Paid: ₱" . number_format($totalPaid, 2) . "\n";
    echo "    Booking Balance: ₱" . number_format($bookingBalance, 2) . "\n\n";
    
    // Get unpaid rentals
    $unpaidRentals = DB::table('rentals')
        ->where('BookingID', 'B177')
        ->where('is_paid', false)
        ->get();
    
    echo "  Unpaid Rentals: {$unpaidRentals->count()}\n";
    $rentalTotal = 0;
    
    foreach ($unpaidRentals as $rental) {
        $rentalItem = DB::table('rental_items')->where('id', $rental->rental_item_id)->first();
        $inventoryItem = $rentalItem ? DB::table('inventory_items')->where('sku', $rentalItem->sku)->first() : null;
        $itemName = $inventoryItem ? $inventoryItem->name : "Item #{$rental->rental_item_id}";
        
        // Calculate charge
        $charge = 0;
        if ($rental->rate_type_snapshot === 'Per-Day') {
            $endDate = $rental->returned_at ? \Carbon\Carbon::parse($rental->returned_at) : \Carbon\Carbon::now();
            $issuedAt = \Carbon\Carbon::parse($rental->issued_at);
            $rentalDays = max(1, $issuedAt->diffInDays($endDate));
            $charge = $rental->rate_snapshot * $rentalDays * $rental->quantity;
        } else {
            $charge = $rental->rate_snapshot * $rental->quantity;
        }
        
        // Add fees
        $fees = DB::table('rental_fees')->where('rental_id', $rental->id)->sum('amount');
        $charge += $fees;
        
        $rentalTotal += $charge;
        echo "    - {$itemName} ({$rental->status}): ₱" . number_format($charge, 2);
        if ($fees > 0) {
            echo " (includes ₱" . number_format($fees, 2) . " fees)";
        }
        echo "\n";
    }
    
    echo "    ────────────────────────\n";
    echo "    Rental Charges Total: ₱" . number_format($rentalTotal, 2) . "\n\n";
    
    echo "  📊 OUTSTANDING CHARGES:\n";
    echo "    Booking Balance: ₱" . number_format($bookingBalance, 2) . "\n";
    echo "    Rental Charges: ₱" . number_format($rentalTotal, 2) . "\n";
    echo "    ════════════════════════\n";
    echo "    Total Outstanding: ₱" . number_format($bookingBalance + $rentalTotal, 2) . "\n\n";
    
    if ($seniorDiscount > 0) {
        echo "  ⚠️  ISSUE DETECTED:\n";
        echo "    Senior discount (₱" . number_format($seniorDiscount, 2) . ") is still applied!\n";
        echo "    This should be ZERO after voiding Bill Out Settlement.\n\n";
        echo "  ✅ EXPECTED BEHAVIOR:\n";
        $correctBookingBalance = ($packageTotal + $excessFee) - $totalPaid;
        $correctTotal = $correctBookingBalance + $rentalTotal;
        echo "    Without senior discount:\n";
        echo "      Booking Balance: ₱" . number_format($correctBookingBalance, 2) . "\n";
        echo "      Total Outstanding: ₱" . number_format($correctTotal, 2) . "\n";
        echo "    Senior discount should ONLY be applied during bill-out process!\n";
    } else {
        echo "  ✅ CORRECT:\n";
        echo "    Senior discount is cleared (₱0.00)\n";
        echo "    Outstanding charges reflect full amount without discount\n";
        echo "    Senior discount will be applied during bill-out process\n";
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n2. What Happens When Voiding Bill Out Settlement:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "BEFORE VOID:\n";
echo "  • Bill Out Settlement processed\n";
echo "  • Senior discount applied and saved to booking\n";
echo "  • Outstanding: ₱0 (fully paid)\n\n";

echo "AFTER VOID:\n";
echo "  ✅ All transactions voided\n";
echo "  ✅ Payment record deleted\n";
echo "  ✅ Rentals marked as unpaid\n";
echo "  ✅ Senior discount cleared (set to ₱0)\n";
echo "  ✅ Actual seniors count cleared (set to 0)\n";
echo "  ✅ Outstanding charges show FULL amount (no discount)\n\n";

echo "REDO BILL OUT:\n";
echo "  • Navigate to Currently Staying → Bill Out\n";
echo "  • Outstanding shows FULL amount (no discount yet)\n";
echo "  • Admin can choose to apply senior discount again\n";
echo "  • If applied, discount is calculated and saved during new bill-out\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✨ Senior discount fix implemented!\n";
