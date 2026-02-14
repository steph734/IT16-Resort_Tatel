<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;
use App\Mail\BookingConfirmationMail;

class SendBookingConfirmation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:send-confirmation {booking_id? : The booking ID to send confirmation email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send booking confirmation email to guest';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bookingId = $this->argument('booking_id');

        if (!$bookingId) {
            // Get the latest booking
            $booking = Booking::with(['guest', 'package', 'payments'])
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$booking) {
                $this->error('No bookings found in the database.');
                return 1;
            }
            
            $this->info("Using latest booking: {$booking->BookingID}");
        } else {
            $booking = Booking::with(['guest', 'package', 'payments'])
                ->where('BookingID', $bookingId)
                ->first();
            
            if (!$booking) {
                $this->error("Booking {$bookingId} not found.");
                return 1;
            }
        }

        // Get the latest payment or create a dummy one
        $payment = $booking->payments()->latest()->first();
        
        if (!$payment) {
            $this->warn('No payment found for this booking. Creating dummy payment for testing...');
            $payment = new \App\Models\Payment([
                'BookingID' => $booking->BookingID,
                'PaymentDate' => now(),
                'Amount' => $booking->TotalAmount * 0.5,
                'TotalAmount' => $booking->TotalAmount,
                'PaymentMethod' => 'gcash',
                'PaymentStatus' => 'For Verification',
                'PaymentPurpose' => 'Downpayment',
            ]);
        }

        $this->info('Booking Details:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Booking ID', $booking->BookingID],
                ['Guest Name', $booking->guest->FName . ' ' . $booking->guest->LName],
                ['Email', $booking->guest->Email],
                ['Check-in', $booking->CheckInDate],
                ['Check-out', $booking->CheckOutDate],
                ['Package', $booking->package->Name ?? 'N/A'],
                ['Total Amount', '₱' . number_format($booking->TotalAmount ?? 0, 2)],
            ]
        );

        if ($this->confirm('Send test email to ' . $booking->guest->Email . '?', true)) {
            try {
                Mail::to($booking->guest->Email)->send(new BookingConfirmationMail($booking, $payment));
                $this->info('✅ Email sent successfully!');
                
                if (config('mail.default') === 'log') {
                    $this->warn('📝 Email was logged to storage/logs/laravel.log (MAIL_MAILER=log)');
                    $this->info('Check the log file to see the email content.');
                }
                
                return 0;
            } catch (\Exception $e) {
                $this->error('❌ Email sending failed: ' . $e->getMessage());
                $this->error('Check storage/logs/laravel.log for details.');
                return 1;
            }
        } else {
            $this->info('Email sending cancelled.');
            return 0;
        }
    }
}
