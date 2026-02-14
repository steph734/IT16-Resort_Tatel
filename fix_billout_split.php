<?php

/**
 * Script to fix existing Bill Out Settlement transactions
 * Splits them into separate booking and rental transactions
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🔄 Starting to fix existing Bill Out Settlement transactions...\n\n";

// Get all Bill Out Settlement transactions
$billoutTransactions = DB::table('transactions')
    ->where('transaction_type', 'booking')
    ->where('purpose', 'Bill Out Settlement')
    ->get();

echo "Found {$billoutTransactions->count()} Bill Out Settlement transaction(s).\n\n";

if ($billoutTransactions->isEmpty()) {
    echo "✅ No transactions to process.\n";
    exit(0);
}

// Group by reference_id to handle potential duplicates
$groupedTransactions = $billoutTransactions->groupBy('reference_id');

$processedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($groupedTransactions as $referenceId => $transactions) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Processing Payment Reference: {$referenceId}\n";
    echo "Found {$transactions->count()} transaction(s) with this reference\n\n";
    
    // Get the payment
    $payment = DB::table('payments')->where('PaymentID', $referenceId)->first();
    
    if (!$payment) {
        echo "  ❌ Payment not found. Skipping.\n\n";
        $skippedCount++;
        continue;
    }
    
    // Get the booking
    $booking = DB::table('bookings')->where('BookingID', $payment->BookingID)->first();
    
    if (!$booking) {
        echo "  ❌ Booking not found. Skipping.\n\n";
        $skippedCount++;
        continue;
    }
    
    // Get guest info
    $guest = DB::table('guests')->where('GuestID', $booking->GuestID)->first();
    $customerName = $guest ? trim($guest->FName . ' ' . $guest->LName) : 'Unknown';
    
    echo "  Booking: {$booking->BookingID}\n";
    echo "  Guest: {$customerName}\n";
    echo "  Payment Amount: ₱" . number_format($payment->Amount, 2) . "\n\n";
    
    // Check if already split
    $existingRentalTransactions = DB::table('transactions')
        ->where('reference_id', $referenceId)
        ->where('transaction_type', 'rental')
        ->count();
    
    if ($existingRentalTransactions > 0) {
        echo "  ⚠️  Already has {$existingRentalTransactions} rental transaction(s). Skipping.\n\n";
        $skippedCount++;
        continue;
    }
    
    // Get rentals for this booking
    $rentals = DB::table('rentals')
        ->where('BookingID', $booking->BookingID)
        ->where('is_paid', true)
        ->get();
    
    if ($rentals->isEmpty()) {
        echo "  ℹ️  No paid rentals found. This is booking-only transaction.\n\n";
        $skippedCount++;
        continue;
    }
    
    echo "  Found {$rentals->count()} rental(s) to split:\n";
    
    // Start transaction
    DB::beginTransaction();
    
    try {
        // Calculate booking balance
        $checkIn = Carbon::parse($booking->CheckInDate);
        $checkOut = Carbon::parse($booking->CheckOutDate);
        $days = $checkIn->diffInDays($checkOut);
        
        // Get package price
        $package = DB::table('packages')->where('PackageID', $booking->PackageID)->first();
        $packagePrice = $package ? $package->Price : 0;
        $packageTotal = $packagePrice * $days;
        $excessFee = $booking->ExcessFee ?? 0;
        $seniorDiscount = $booking->senior_discount ?? 0;
        
        // Calculate total booking cost
        $bookingTotal = $packageTotal + $excessFee - $seniorDiscount;
        
        // Get previously paid amount (before bill out)
        $previouslyPaid = DB::table('payments')
            ->where('BookingID', $booking->BookingID)
            ->where('PaymentID', '!=', $referenceId)
            ->whereIn('PaymentPurpose', ['Booking Payment', 'Reservation Fee', 'Downpayment', 'Full Payment'])
            ->sum('Amount');
        
        $bookingBalance = max(0, $bookingTotal - $previouslyPaid);
        
        // Calculate total rental charges
        $totalRentalCharges = 0;
        $rentalBreakdown = [];
        
        foreach ($rentals as $rental) {
            // Get rental item info
            $rentalItem = DB::table('rental_items')->where('id', $rental->rental_item_id)->first();
            $inventoryItem = $rentalItem ? DB::table('inventory_items')->where('sku', $rentalItem->sku)->first() : null;
            $itemName = $inventoryItem ? $inventoryItem->name : "Item #{$rental->rental_item_id}";
            
            // Calculate base rental charge
            $baseCharge = 0;
            if ($rental->rate_type_snapshot === 'Per-Day') {
                $endDate = $rental->returned_at ? Carbon::parse($rental->returned_at) : Carbon::now();
                $issuedAt = Carbon::parse($rental->issued_at);
                $rentalDays = $issuedAt->diffInDays($endDate);
                $rentalDays = max(1, $rentalDays);
                $baseCharge = $rental->rate_snapshot * $rentalDays * $rental->quantity;
            } else {
                $baseCharge = $rental->rate_snapshot * $rental->quantity;
            }
            
            $totalRentalCharges += $baseCharge;
            
            // Get fees
            $fees = DB::table('rental_fees')->where('rental_id', $rental->id)->get();
            foreach ($fees as $fee) {
                $totalRentalCharges += $fee->amount;
            }
            
            $rentalBreakdown[] = [
                'rental' => $rental,
                'item_name' => $itemName,
                'base_charge' => $baseCharge,
                'fees' => $fees
            ];
            
            echo "    - {$itemName}: ₱" . number_format($baseCharge, 2);
            if ($fees->isNotEmpty()) {
                $feeTotal = $fees->sum('amount');
                echo " + ₱" . number_format($feeTotal, 2) . " fees";
            }
            echo "\n";
        }
        
        echo "\n  Breakdown:\n";
        echo "    Booking Balance: ₱" . number_format($bookingBalance, 2) . "\n";
        echo "    Rental Charges: ₱" . number_format($totalRentalCharges, 2) . "\n";
        echo "    Total: ₱" . number_format($bookingBalance + $totalRentalCharges, 2) . "\n";
        echo "    Payment Amount: ₱" . number_format($payment->Amount, 2) . "\n\n";
        
        // Delete or update existing transactions
        if ($transactions->count() > 1) {
            echo "  ⚠️  Found {$transactions->count()} duplicate transactions. Keeping the latest one.\n";
            // Keep the one with the correct amount (matching payment)
            $correctTransaction = $transactions->firstWhere('amount', $payment->Amount);
            if (!$correctTransaction) {
                $correctTransaction = $transactions->sortByDesc('transaction_id')->first();
            }
            
            foreach ($transactions as $trans) {
                if ($trans->transaction_id != $correctTransaction->transaction_id) {
                    echo "    Deleting duplicate transaction ID: {$trans->transaction_id}\n";
                    DB::table('transactions')->where('transaction_id', $trans->transaction_id)->delete();
                }
            }
            $transaction = $correctTransaction;
        } else {
            $transaction = $transactions->first();
        }
        
        echo "\n  Processing Transaction ID: {$transaction->transaction_id}\n";
        
        // Update the original transaction to be booking balance only (or delete if $0)
        if ($bookingBalance > 0) {
            DB::table('transactions')
                ->where('transaction_id', $transaction->transaction_id)
                ->update([
                    'amount' => $bookingBalance,
                    'metadata' => json_encode([
                        'booking_balance' => $bookingBalance,
                        'rental_charges' => $totalRentalCharges,
                        'senior_discount' => $seniorDiscount,
                        'split_from_transaction' => $transaction->transaction_id,
                        'split_date' => now()->toDateTimeString(),
                        'source' => 'admin_bill_out'
                    ]),
                    'updated_at' => now()
                ]);
            echo "    ✅ Updated original transaction to booking balance only\n";
        } else {
            DB::table('transactions')->where('transaction_id', $transaction->transaction_id)->delete();
            echo "    ✅ Deleted original transaction (booking balance is ₱0)\n";
        }
        
        // Create rental transactions
        foreach ($rentalBreakdown as $breakdown) {
            $rental = $breakdown['rental'];
            $itemName = $breakdown['item_name'];
            $baseCharge = $breakdown['base_charge'];
            $fees = $breakdown['fees'];
            
            // Create transaction for base rental charge
            if ($baseCharge > 0) {
                $rentalTransactionId = DB::table('transactions')->insertGetId([
                    'transaction_type' => 'rental',
                    'reference_id' => $referenceId,
                    'transaction_date' => $transaction->transaction_date,
                    'amount' => $baseCharge,
                    'payment_method' => $transaction->payment_method,
                    'payment_status' => 'Fully Paid',
                    'purpose' => 'Bill Out Settlement - ' . $itemName,
                    'booking_id' => $booking->BookingID,
                    'rental_id' => $rental->id,
                    'guest_id' => $booking->GuestID,
                    'customer_name' => $customerName,
                    'customer_email' => $guest->Email ?? null,
                    'customer_phone' => $guest->Phone ?? null,
                    'processed_by' => $transaction->processed_by,
                    'processor_name' => $transaction->processor_name,
                    'metadata' => json_encode([
                        'rental_item_id' => $rental->rental_item_id,
                        'rental_item_name' => $itemName,
                        'quantity' => $rental->quantity,
                        'rate' => $rental->rate_snapshot,
                        'rate_type' => $rental->rate_type_snapshot,
                        'split_from_transaction' => $transaction->transaction_id,
                        'split_date' => now()->toDateTimeString(),
                        'source' => 'admin_bill_out'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                echo "    ✅ Created rental transaction (ID: {$rentalTransactionId}) for {$itemName}\n";
            }
            
            // Create fee transactions
            foreach ($fees as $fee) {
                $feeType = $fee->type === 'Damage' ? 'DF' : 'LF';
                $feeTransactionId = DB::table('transactions')->insertGetId([
                    'transaction_type' => 'rental',
                    'reference_id' => $referenceId,
                    'transaction_date' => $transaction->transaction_date,
                    'amount' => $fee->amount,
                    'payment_method' => $transaction->payment_method,
                    'payment_status' => 'Fully Paid',
                    'purpose' => 'Bill Out Settlement - ' . $itemName . ' ' . $feeType,
                    'booking_id' => $booking->BookingID,
                    'rental_id' => $rental->id,
                    'guest_id' => $booking->GuestID,
                    'customer_name' => $customerName,
                    'customer_email' => $guest->Email ?? null,
                    'customer_phone' => $guest->Phone ?? null,
                    'processed_by' => $transaction->processed_by,
                    'processor_name' => $transaction->processor_name,
                    'metadata' => json_encode([
                        'rental_item_id' => $rental->rental_item_id,
                        'rental_item_name' => $itemName,
                        'fee_type' => $fee->type,
                        'fee_reason' => $fee->reason,
                        'split_from_transaction' => $transaction->transaction_id,
                        'split_date' => now()->toDateTimeString(),
                        'source' => 'admin_bill_out'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                echo "    ✅ Created fee transaction (ID: {$feeTransactionId}) for {$itemName} {$feeType}\n";
            }
        }
        
        DB::commit();
        echo "\n  ✅ Successfully processed!\n\n";
        $processedCount++;
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "\n  ❌ Error: " . $e->getMessage() . "\n\n";
        $errorCount++;
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 Summary:\n";
echo "  ✅ Processed: {$processedCount}\n";
echo "  ⚠️  Skipped: {$skippedCount}\n";
echo "  ❌ Errors: {$errorCount}\n";
echo "\n✨ Done!\n";
