<?php

/**
 * Script to split existing "Bill Out Settlement" transactions
 * into separate booking and rental transactions
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

echo "Starting to fix existing Bill Out Settlement transactions...\n\n";

// Find all Bill Out Settlement transactions with type='booking'
$billoutTransactions = Transaction::where('transaction_type', 'booking')
    ->where('purpose', 'Bill Out Settlement')
    ->with('booking.guest', 'booking.rentals.rentalItem', 'booking.rentals.fees')
    ->get();

echo "Found " . $billOutTransactions->count() . " Bill Out Settlement transactions to process.\n\n";

$processedCount = 0;
$skippedCount = 0;

foreach ($billOutTransactions as $transaction) {
    echo "Processing Transaction ID: {$transaction->id} | Reference: {$transaction->reference_id}\n";
    
    $payment = $transaction->payment;
    $booking = $transaction->booking;
    
    if (!$payment || !$booking) {
        echo "  ⚠️  Skipping - Payment or Booking not found\n\n";
        $skippedCount++;
        continue;
    }
    
    // Check if this transaction has already been split (look for rental transactions with same reference_id)
    $existingRentalTransactions = Transaction::where('reference_id', $payment->PaymentID)
        ->where('transaction_type', 'rental')
        ->count();
    
    if ($existingRentalTransactions > 0) {
        echo "  ⚠️  Skipping - Already has {$existingRentalTransactions} rental transaction(s)\n\n";
        $skippedCount++;
        continue;
    }
    
    DB::beginTransaction();
    
    try {
        // Get metadata to extract amounts
        $metadata = $transaction->metadata ?? [];
        $bookingBalance = $metadata['booking_balance'] ?? 0;
        $rentalCharges = $metadata['rental_charges'] ?? 0;
        
        // Get customer details
        $customerName = trim(($booking->guest->FName ?? '') . ' ' . ($booking->guest->LName ?? ''));
        
        // Get unpaid rentals at the time of this transaction
        $unpaidRentals = $booking->rentals()
            ->whereIn('status', ['Issued', 'Returned', 'Lost', 'Damaged'])
            ->with('rentalItem', 'fees')
            ->get();
        
        if ($unpaidRentals->isEmpty()) {
            echo "  ℹ️  No rentals found for this booking - keeping as booking transaction only\n";
            
            // Update the existing transaction to reflect only booking balance
            $transaction->update([
                'amount' => $bookingBalance > 0 ? $bookingBalance : $transaction->amount,
            ]);
            
            DB::commit();
            $processedCount++;
            echo "  ✅ Updated (booking only)\n\n";
            continue;
        }
        
        echo "  Found " . $unpaidRentals->count() . " rental item(s)\n";
        
        // Update existing transaction to be booking balance only (if > 0)
        if ($bookingBalance > 0) {
            $transaction->update([
                'amount' => $bookingBalance,
                'metadata' => array_merge($metadata, [
                    'split_transaction' => true,
                    'split_date' => now()->toDateTimeString(),
                ]),
            ]);
            echo "  ✅ Updated existing transaction to booking balance: ₱" . number_format($bookingBalance, 2) . "\n";
        } else {
            // If no booking balance, delete this transaction or mark as $0
            $transaction->delete();
            echo "  ✅ Deleted original transaction (no booking balance)\n";
        }
        
        // Create separate transactions for each rental item
        $rentalTransactionCount = 0;
        
        foreach ($unpaidRentals as $rental) {
            $rentalItemName = $rental->rentalItem->name ?? 'Unknown Item';
            
            // Calculate base rental charge
            $baseCharge = 0;
            if ($rental->rate_type_snapshot === 'Per-Day') {
                $endDate = $rental->returned_at ?? \Carbon\Carbon::parse($transaction->transaction_date);
                $rentalDays = $rental->issued_at->diffInDays($endDate);
                $rentalDays = max(1, $rentalDays);
                $baseCharge = $rental->rate_snapshot * $rentalDays * $rental->quantity;
            } else {
                $baseCharge = $rental->rate_snapshot * $rental->quantity;
            }
            
            // Create transaction for base rental charge
            if ($baseCharge > 0) {
                Transaction::create([
                    'transaction_type' => 'rental',
                    'reference_id' => $payment->PaymentID,
                    'transaction_date' => $transaction->transaction_date,
                    'amount' => $baseCharge,
                    'payment_method' => $transaction->payment_method,
                    'payment_status' => $transaction->payment_status,
                    'purpose' => 'Bill Out Settlement - ' . $rentalItemName,
                    'booking_id' => $booking->BookingID,
                    'rental_id' => $rental->id,
                    'guest_id' => $booking->GuestID,
                    'customer_name' => $customerName,
                    'customer_email' => $booking->guest->Email ?? null,
                    'customer_phone' => $booking->guest->Phone ?? null,
                    'processed_by' => $transaction->processed_by,
                    'processor_name' => $transaction->processor_name,
                    'amount_received' => null,
                    'change_amount' => null,
                    'reference_number' => $transaction->reference_number,
                    'metadata' => [
                        'rental_item_id' => $rental->rental_item_id,
                        'rental_item_name' => $rentalItemName,
                        'quantity' => $rental->quantity,
                        'rate' => $rental->rate_snapshot,
                        'rate_type' => $rental->rate_type_snapshot,
                        'source' => 'admin_bill_out',
                        'split_from_transaction' => $transaction->id,
                        'split_date' => now()->toDateTimeString(),
                    ],
                ]);
                
                $rentalTransactionCount++;
                echo "  ✅ Created rental transaction: {$rentalItemName} - ₱" . number_format($baseCharge, 2) . "\n";
            }
            
            // Create separate transactions for each damage/loss fee
            $fees = $rental->fees()->get();
            foreach ($fees as $fee) {
                $feeType = $fee->type === 'Damage' ? 'DF' : 'LF';
                
                Transaction::create([
                    'transaction_type' => 'rental',
                    'reference_id' => $payment->PaymentID,
                    'transaction_date' => $transaction->transaction_date,
                    'amount' => $fee->amount,
                    'payment_method' => $transaction->payment_method,
                    'payment_status' => $transaction->payment_status,
                    'purpose' => 'Bill Out Settlement - ' . $rentalItemName . ' ' . $feeType,
                    'booking_id' => $booking->BookingID,
                    'rental_id' => $rental->id,
                    'guest_id' => $booking->GuestID,
                    'customer_name' => $customerName,
                    'customer_email' => $booking->guest->Email ?? null,
                    'customer_phone' => $booking->guest->Phone ?? null,
                    'processed_by' => $transaction->processed_by,
                    'processor_name' => $transaction->processor_name,
                    'amount_received' => null,
                    'change_amount' => null,
                    'reference_number' => $transaction->reference_number,
                    'metadata' => [
                        'rental_item_id' => $rental->rental_item_id,
                        'rental_item_name' => $rentalItemName,
                        'fee_type' => $fee->type,
                        'fee_reason' => $fee->reason,
                        'source' => 'admin_bill_out',
                        'split_from_transaction' => $transaction->id,
                        'split_date' => now()->toDateTimeString(),
                    ],
                ]);
                
                $rentalTransactionCount++;
                echo "  ✅ Created fee transaction: {$rentalItemName} {$feeType} - ₱" . number_format($fee->amount, 2) . "\n";
            }
        }
        
        DB::commit();
        $processedCount++;
        echo "  ✅ Successfully split into {$rentalTransactionCount} rental transaction(s)\n\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "  ❌ Error: " . $e->getMessage() . "\n\n";
        $skippedCount++;
    }
}

echo "\n========================================\n";
echo "Processing complete!\n";
echo "✅ Successfully processed: {$processedCount}\n";
echo "⚠️  Skipped: {$skippedCount}\n";
echo "========================================\n";
