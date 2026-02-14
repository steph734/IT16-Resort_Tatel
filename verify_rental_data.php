<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\InventoryItem;
use App\Models\RentalItem;
use App\Models\PurchaseEntry;
use App\Models\Rental;
use App\Models\RentalFee;
use App\Models\Booking;

echo "\n";
echo str_repeat("=", 100) . "\n";
echo "RENTAL SUBSYSTEM DATA VERIFICATION\n";
echo str_repeat("=", 100) . "\n\n";

// 1. Inventory Items Summary
echo "1. INVENTORY ITEMS (25 items across 4 categories)\n";
echo str_repeat("-", 100) . "\n";
$categories = ['cleaning', 'kitchen', 'amenity', 'rental_item'];
foreach ($categories as $category) {
    $count = InventoryItem::where('category', $category)->count();
    $totalQty = InventoryItem::where('category', $category)->sum('quantity_on_hand');
    echo sprintf("   %-15s: %2d items, %4d units in stock\n", ucfirst($category), $count, $totalQty);
}
echo "\n";

// 2. Rental Items Summary
echo "2. RENTAL ITEMS (10 rentable items with rate configurations)\n";
echo str_repeat("-", 100) . "\n";
$rentalItems = RentalItem::with('inventoryItem')->get();
echo sprintf("   %-5s | %-30s | %-10s | %10s | %6s\n", "SKU", "Item Name", "Rate Type", "Rate", "Stock");
echo str_repeat("-", 100) . "\n";
foreach ($rentalItems as $item) {
    echo sprintf("   %-5s | %-30s | %-10s | ₱%9.2f | %6d\n", 
        $item->sku, 
        substr($item->inventoryItem->name ?? 'N/A', 0, 30),
        $item->rate_type,
        $item->rate,
        $item->inventoryItem->quantity_on_hand ?? 0
    );
}
echo "\n";

// 3. Purchase Entries Summary
echo "3. PURCHASE ENTRIES (5 purchases in last 2-3 months)\n";
echo str_repeat("-", 100) . "\n";
$purchases = PurchaseEntry::with('items')->orderBy('purchase_date', 'desc')->get();
echo sprintf("   %-8s | %-12s | %-30s | %8s | %12s\n", "Entry #", "Date", "Vendor", "Items", "Total");
echo str_repeat("-", 100) . "\n";
foreach ($purchases as $purchase) {
    echo sprintf("   %-8s | %-12s | %-30s | %8d | ₱%10.2f\n",
        $purchase->entry_number,
        $purchase->purchase_date->format('Y-m-d'),
        substr($purchase->vendor_name, 0, 30),
        $purchase->items->count(),
        $purchase->total_amount
    );
}
echo "\n";

// 4. Rental Transactions Summary
echo "4. RENTAL TRANSACTIONS (172 rentals from 56 bookings)\n";
echo str_repeat("-", 100) . "\n";
$totalRentals = Rental::count();
$bookingsWithRentals = Rental::distinct('BookingID')->count('BookingID');
$conditions = Rental::selectRaw('`condition`, COUNT(*) as count')
    ->groupBy('condition')
    ->pluck('count', 'condition')
    ->toArray();

echo "   Total Rentals: {$totalRentals}\n";
echo "   Bookings with Rentals: {$bookingsWithRentals} / " . Booking::where('BookingStatus', 'Completed')->count() . " completed bookings\n";
echo "   Return Conditions:\n";
foreach (['Good', 'Damaged', 'Lost'] as $cond) {
    $count = $conditions[$cond] ?? 0;
    $pct = $totalRentals > 0 ? round($count / $totalRentals * 100, 1) : 0;
    echo sprintf("      • %-10s: %3d rentals (%5.1f%%)\n", $cond, $count, $pct);
}
echo "\n";

// 5. Rental Fees Summary
echo "5. RENTAL FEES (Damage & Loss charges)\n";
echo str_repeat("-", 100) . "\n";
$fees = RentalFee::selectRaw('type, COUNT(*) as count, SUM(amount) as total')
    ->groupBy('type')
    ->get();
echo sprintf("   %-15s | %10s | %15s\n", "Fee Type", "Count", "Total Amount");
echo str_repeat("-", 100) . "\n";
foreach ($fees as $fee) {
    echo sprintf("   %-15s | %10d | ₱%13.2f\n", $fee->type, $fee->count, $fee->total);
}
echo "\n";

// 6. Sample Rental Details (First 5 with fees)
echo "6. SAMPLE RENTALS WITH FEES\n";
echo str_repeat("-", 100) . "\n";
$samplesWithFees = Rental::whereHas('fees')
    ->with(['booking.guest', 'rentalItem.inventoryItem', 'fees'])
    ->take(5)
    ->get();

if ($samplesWithFees->count() > 0) {
    foreach ($samplesWithFees as $rental) {
        echo sprintf("   Booking: %-8s | Guest: %-25s | Item: %-20s | Condition: %-10s\n",
            $rental->BookingID,
            substr(($rental->booking->guest->FName ?? '') . ' ' . ($rental->booking->guest->LName ?? ''), 0, 25),
            substr($rental->rentalItem->inventoryItem->name ?? 'N/A', 0, 20),
            $rental->condition
        );
        foreach ($rental->fees as $fee) {
            echo sprintf("      → %s Fee: ₱%.2f - %s\n", $fee->type, $fee->amount, $fee->reason);
        }
    }
} else {
    echo "   No rentals with fees found.\n";
}

echo "\n";
echo str_repeat("=", 100) . "\n";
echo "VERIFICATION COMPLETE\n";
echo str_repeat("=", 100) . "\n\n";
