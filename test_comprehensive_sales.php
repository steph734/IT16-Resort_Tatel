<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use App\Models\UnpaidItem;
use App\Models\Rental;
use App\Models\Booking;
use Carbon\Carbon;

echo "=== COMPREHENSIVE SALES DATA TEST ===\n\n";

// Test 1: Check Payments table structure and data
echo "1. PAYMENTS TABLE:\n";
echo "   Schema check:\n";
try {
    $samplePayment = Payment::first();
    if ($samplePayment) {
        echo "   - Available columns: " . implode(', ', array_keys($samplePayment->getAttributes())) . "\n";
        echo "   - Sample payment: {$samplePayment->PaymentID} - Amount: {$samplePayment->Amount} - Status: {$samplePayment->PaymentStatus} - Date: {$samplePayment->PaymentDate}\n";
    }
    
    $paymentStatuses = Payment::select('PaymentStatus', \DB::raw('COUNT(*) as count'), \DB::raw('SUM(Amount) as total'))
        ->groupBy('PaymentStatus')
        ->get();
    echo "   - Status breakdown:\n";
    foreach ($paymentStatuses as $status) {
        echo "     * {$status->PaymentStatus}: {$status->count} payments, Total: ₱" . number_format($status->total, 2) . "\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Check UnpaidItems table
echo "\n2. UNPAID_ITEMS TABLE:\n";
try {
    $unpaidItemsCount = UnpaidItem::count();
    $paidUnpaidItems = UnpaidItem::where('IsPaid', true)->count();
    $totalUnpaidItems = UnpaidItem::where('IsPaid', true)->sum('TotalAmount');
    echo "   - Total unpaid items: {$unpaidItemsCount}\n";
    echo "   - Paid items: {$paidUnpaidItems}\n";
    echo "   - Total amount from paid items: ₱" . number_format($totalUnpaidItems, 2) . "\n";
    
    if ($paidUnpaidItems > 0) {
        $sample = UnpaidItem::where('IsPaid', true)->first();
        echo "   - Sample: {$sample->ItemID} - {$sample->ItemName} - Qty: {$sample->Quantity} - ₱{$sample->TotalAmount}\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Check Rentals table
echo "\n3. RENTALS TABLE:\n";
try {
    $sampleRental = Rental::first();
    if ($sampleRental) {
        echo "   - Available columns: " . implode(', ', array_keys($sampleRental->getAttributes())) . "\n";
    }
    
    $rentalsCount = Rental::count();
    $paidRentals = Rental::where('is_paid', true)->count();
    echo "   - Total rentals: {$rentalsCount}\n";
    echo "   - Paid rentals: {$paidRentals}\n";
    
    if ($paidRentals > 0) {
        $paidRentalsData = Rental::where('is_paid', true)->get();
        $totalRentalCharges = 0;
        foreach ($paidRentalsData as $rental) {
            try {
                $charges = $rental->calculateTotalCharges();
                $totalRentalCharges += $charges;
                echo "   - Rental ID {$rental->id}: ₱" . number_format($charges, 2) . " (Status: {$rental->status}, Issued: {$rental->issued_at}, Returned: {$rental->returned_at})\n";
            } catch (\Exception $e) {
                echo "   - Rental ID {$rental->id}: ERROR calculating charges - " . $e->getMessage() . "\n";
            }
        }
        echo "   - Total charges from paid rentals: ₱" . number_format($totalRentalCharges, 2) . "\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 4: Check relationships
echo "\n4. RELATIONSHIPS TEST:\n";
try {
    $rental = Rental::with(['booking.guest', 'rentalItem', 'fees'])->first();
    if ($rental) {
        echo "   - Rental {$rental->id}:\n";
        echo "     * Booking: " . ($rental->booking ? $rental->booking->BookingID : 'NOT LOADED') . "\n";
        echo "     * Guest: " . ($rental->booking && $rental->booking->guest ? $rental->booking->guest->FName : 'NOT LOADED') . "\n";
        echo "     * Rental Item: " . ($rental->rentalItem ? $rental->rentalItem->name : 'NOT LOADED') . "\n";
        echo "     * Fees count: " . $rental->fees->count() . "\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Test 5: This month's data (November 2025)
echo "\n5. NOVEMBER 2025 DATA:\n";
$startDate = Carbon::parse('2025-11-01')->startOfDay();
$endDate = Carbon::parse('2025-11-30')->endOfDay();

echo "   Date range: {$startDate->toDateString()} to {$endDate->toDateString()}\n\n";

try {
    $bookingSales = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
        ->whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])
        ->sum('Amount');
    echo "   - Booking sales: ₱" . number_format($bookingSales, 2) . "\n";
    
    $bookingCount = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
        ->whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])
        ->count();
    echo "   - Number of payments: {$bookingCount}\n";
    
    if ($bookingCount > 0) {
        $novemberPayments = Payment::whereBetween('PaymentDate', [$startDate, $endDate])
            ->whereIn('PaymentStatus', ['Verified', 'Paid', 'Fully Paid', 'Downpayment'])
            ->get();
        echo "   - Payment details:\n";
        foreach ($novemberPayments as $payment) {
            echo "     * {$payment->PaymentID}: ₱{$payment->Amount} - {$payment->PaymentStatus} - {$payment->PaymentDate}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ERROR with booking sales: " . $e->getMessage() . "\n";
}

try {
    $unpaidItemSales = UnpaidItem::where('IsPaid', true)
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->sum('TotalAmount');
    echo "   - Unpaid items sales: ₱" . number_format($unpaidItemSales, 2) . "\n";
} catch (\Exception $e) {
    echo "   ERROR with unpaid items: " . $e->getMessage() . "\n";
}

try {
    $rentalSales = Rental::where('is_paid', true)
        ->whereBetween('returned_at', [$startDate, $endDate])
        ->get()
        ->sum(function($rental) {
            return $rental->calculateTotalCharges();
        });
    echo "   - Rental sales (new system): ₱" . number_format($rentalSales, 2) . "\n";
} catch (\Exception $e) {
    echo "   ERROR with rental sales: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
