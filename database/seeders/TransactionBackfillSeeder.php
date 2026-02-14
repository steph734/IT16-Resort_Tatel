<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalFee;
use Illuminate\Support\Facades\DB;

class TransactionBackfillSeeder extends Seeder
{
    /**
     * Backfill transactions table from existing payments and rentals
     */
    public function run(): void
    {
        // Clear existing transactions
        Transaction::truncate();

        $this->command->info('Backfilling transactions from payments...');
        $this->backfillBookingPayments();

        $this->command->info('Backfilling transactions from rentals...');
        $this->backfillRentalTransactions();

        $this->command->info('Transaction backfill completed!');
    }

    private function backfillBookingPayments()
    {
        $payments = Payment::with(['booking.guest'])->get();
        
        foreach ($payments as $payment) {
            $booking = $payment->booking;
            $guest = $booking->guest ?? null;

            Transaction::create([
                'transaction_type' => 'booking',
                'reference_id' => 'TXN-B' . str_pad($payment->PaymentID, 5, '0', STR_PAD_LEFT),
                'transaction_date' => $payment->PaymentDate,
                'amount' => $payment->Amount ?? 0,
                'payment_method' => $this->normalizePaymentMethod($payment->PaymentMethod),
                'payment_status' => $this->normalizePaymentStatus($booking->payment_status ?? 'Fully Paid'),
                'purpose' => $this->determinePurpose($payment, $booking),
                'booking_id' => $booking->BookingID,
                'guest_id' => $guest?->GuestID,
                'customer_name' => $guest ? "{$guest->FName} {$guest->LName}" : 'Unknown Guest',
                'customer_email' => $guest?->Email,
                'customer_phone' => $guest?->ContactNum,
                'processed_by' => $payment->processed_by,
                'processor_name' => 'Seeded Data',
                'amount_received' => $payment->PaymentMethod === 'Cash' ? ($payment->amount_received ?? $payment->Amount) : null,
                'change_amount' => $payment->change_amount ?? 0,
                'metadata' => json_encode([
                    'payment_id' => $payment->PaymentID,
                    'booking_id' => $booking->BookingID,
                    'checkin_date' => $booking->CheckInDate,
                    'checkout_date' => $booking->CheckOutDate,
                ]),
                'reference_number' => $payment->PaymentProof ?? null,
                'notes' => null,
                'is_voided' => false,
            ]);
        }

        $this->command->info("Created {$payments->count()} booking transactions");
    }

    private function backfillRentalTransactions()
    {
        $rentals = Rental::with(['booking.guest', 'rentalItem', 'fees'])->get();
        
        foreach ($rentals as $rental) {
            $booking = $rental->booking;
            $guest = $booking->guest ?? null;
            $totalCharges = $rental->calculateTotalCharges();

            // Only create transaction if there are charges
            if ($totalCharges > 0) {
                Transaction::create([
                    'transaction_type' => 'rental',
                    'reference_id' => 'TXN-R' . str_pad($rental->id, 5, '0', STR_PAD_LEFT),
                    'transaction_date' => $rental->returned_at ?? $rental->issued_at,
                    'amount' => $totalCharges,
                    'payment_method' => 'Cash', // Default for rentals
                    'payment_status' => 'Fully Paid',
                    'purpose' => $this->determineRentalPurpose($rental),
                    'booking_id' => $booking->BookingID,
                    'guest_id' => $guest?->GuestID,
                    'rental_id' => $rental->id,
                    'customer_name' => $guest ? "{$guest->FName} {$guest->LName}" : 'Unknown Guest',
                    'customer_email' => $guest?->Email,
                    'customer_phone' => $guest?->ContactNum,
                    'processed_by' => $rental->issued_by,
                    'processor_name' => $rental->issuedByUser?->name ?? 'Staff',
                    'amount_received' => $totalCharges,
                    'change_amount' => 0,
                    'metadata' => json_encode([
                        'rental_id' => $rental->id,
                        'booking_id' => $booking->BookingID,
                        'rental_item' => $rental->rentalItem->name,
                        'quantity' => $rental->quantity,
                        'status' => $rental->status,
                        'issued_at' => $rental->issued_at,
                        'returned_at' => $rental->returned_at,
                    ]),
                    'reference_number' => null,
                    'notes' => $rental->notes,
                    'is_voided' => false,
                ]);
            }
        }

        $this->command->info("Created " . $rentals->where(function($r) { return $r->calculateTotalCharges() > 0; })->count() . " rental transactions");
    }

    private function determinePurpose($payment, $booking)
    {
        $totalAmount = $booking->TotalAmount ?? 0;
        $amountPaid = $payment->Amount ?? 0;

        if ($amountPaid >= $totalAmount) {
            return 'full_payment';
        } elseif ($amountPaid == 1000) {
            return 'downpayment';
        } else {
            return 'partial_payment';
        }
    }

    private function determineRentalPurpose($rental)
    {
        switch ($rental->status) {
            case 'Returned':
                return 'rental_return';
            case 'Damaged':
                return 'damage_fee';
            case 'Lost':
                return 'lost_item_fee';
            default:
                return 'rental_charge';
        }
    }

    private function normalizePaymentMethod($method)
    {
        // Map old payment methods to new enum values
        $methodMap = [
            'PayMaya' => 'GCash',
            'Paymongo' => 'GCash',
            'Cash' => 'Cash',
            'GCash' => 'GCash',
            'BDO Transfer' => 'BDO Transfer',
            'BPI Transfer' => 'BPI Transfer',
            'GoTyme' => 'GoTyme',
        ];

        return $methodMap[$method] ?? 'Cash'; // Default to Cash if unknown
    }

    private function normalizePaymentStatus($status)
    {
        // Map old payment statuses to new enum values
        $statusMap = [
            'Fully Paid' => 'Fully Paid',
            'Partial' => 'Partial Payment',
            'Downpayment' => 'Downpayment',
            'Partial Payment' => 'Partial Payment',
            'completed' => 'Fully Paid',
        ];

        return $statusMap[$status] ?? 'Fully Paid'; // Default to Fully Paid if unknown
    }
}
