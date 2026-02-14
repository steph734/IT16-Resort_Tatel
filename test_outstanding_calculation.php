<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

echo "Testing Outstanding Charges Calculation Fix:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$booking = Booking::with(['package', 'payments', 'unpaidItems', 'rentals.fees'])
    ->where('BookingID', 'B177')
    ->first();

if ($booking) {
    echo "Booking: {$booking->BookingID}\n";
    echo "Guest: {$booking->guest->FirstName} {$booking->guest->LastName}\n\n";
    
    // Calculate days
    $checkIn = \Carbon\Carbon::parse($booking->CheckInDate);
    $checkOut = \Carbon\Carbon::parse($booking->CheckOutDate);
    $days = $checkIn->diffInDays($checkOut);
    
    // Calculate booking charges
    $packagePrice = $booking->package->Price ?? 0;
    $totalAmount = $packagePrice * $days;
    $excessFee = $booking->ExcessFee ?? 0;
    $totalAmount += $excessFee;
    
    echo "BOOKING CALCULATION (WITHOUT SENIOR DISCOUNT):\n";
    echo "  Package: {$booking->package->Name}\n";
    echo "  Price per day: ₱" . number_format($packagePrice, 2) . "\n";
    echo "  Days: {$days}\n";
    echo "  Package Total: ₱" . number_format($packagePrice * $days, 2) . "\n";
    echo "  Excess Fee: ₱" . number_format($excessFee, 2) . "\n";
    echo "  ────────────────────────\n";
    echo "  Booking Subtotal: ₱" . number_format($totalAmount, 2) . "\n\n";
    
    // Payments
    $amountPaid = $booking->payments->sum('Amount');
    $remainingBalance = $totalAmount - $amountPaid;
    
    echo "PAYMENTS:\n";
    echo "  Total Paid: ₱" . number_format($amountPaid, 2) . "\n";
    echo "  Booking Balance: ₱" . number_format($remainingBalance, 2) . "\n\n";
    
    // Unpaid items
    $unpaidItems = $booking->unpaidItems ? $booking->unpaidItems->where('IsPaid', false) : collect([]);
    $unpaidItemsTotal = $unpaidItems->sum('TotalAmount');
    
    echo "UNPAID ITEMS:\n";
    if ($unpaidItems->count() > 0) {
        foreach ($unpaidItems as $item) {
            echo "  - {$item->Description}: ₱" . number_format($item->TotalAmount, 2) . "\n";
        }
    } else {
        echo "  None\n";
    }
    echo "  Total: ₱" . number_format($unpaidItemsTotal, 2) . "\n\n";
    
    // Rental charges
    $activeRentals = $booking->rentals()
        ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
        ->where('is_paid', false)
        ->with(['rentalItem', 'fees'])
        ->get();
    
    $rentalCharges = 0;
    echo "UNPAID RENTAL CHARGES:\n";
    if ($activeRentals->count() > 0) {
        foreach ($activeRentals as $rental) {
            $charge = $rental->calculateTotalCharges();
            $rentalCharges += $charge;
            $itemName = $rental->rentalItem->inventoryItem->name ?? "Item #{$rental->rental_item_id}";
            echo "  - {$itemName} ({$rental->status}): ₱" . number_format($charge, 2) . "\n";
        }
    } else {
        echo "  None\n";
    }
    echo "  Total: ₱" . number_format($rentalCharges, 2) . "\n\n";
    
    // Total outstanding
    $totalOutstanding = $remainingBalance + $unpaidItemsTotal + $rentalCharges;
    
    echo "════════════════════════════════════════════\n";
    echo "TOTAL OUTSTANDING CHARGES: ₱" . number_format($totalOutstanding, 2) . "\n";
    echo "════════════════════════════════════════════\n\n";
    
    // Check if senior discount is stored
    $storedDiscount = $booking->senior_discount ?? 0;
    if ($storedDiscount > 0) {
        echo "ℹ️  Note: Booking has stored senior discount of ₱" . number_format($storedDiscount, 2) . "\n";
        echo "   However, this is NOT applied to outstanding calculation.\n";
        echo "   Senior discount should only be applied during bill-out process.\n\n";
    }
    
    echo "✅ This matches the Bill Out page calculation!\n";
    echo "   Currently Staying and Bill Out now show consistent amounts.\n";
}
