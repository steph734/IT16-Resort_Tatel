<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingReminderMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Guest;
use Carbon\Carbon;

class SendBookingReminder extends Command
{
    protected $signature = 'reminder:send-booking';
    protected $description = 'Send reminder emails to guests who still have remaining balance and whose check-in date is near';

    public function handle()
    {
        $today = Carbon::today();
        $threeDaysFromNow = Carbon::today()->addDays(14);

        // ✅ Fetch bookings within the next 14 days
        $bookings = Booking::with('guest')
            ->whereBetween('CheckInDate', [$today, $threeDaysFromNow])
            ->get();

        if ($bookings->isEmpty()) {
            $this->warn("⚠️ No upcoming bookings within the next 14 days.");
            return;
        }

        foreach ($bookings as $booking) {
            $payment = Payment::where('BookingID', $booking->BookingID)
                              ->orderBy('created_at', 'desc')
                              ->first();

            if (!$payment) {
                $this->warn("⚠️ No payment found for BookingID: {$booking->BookingID}");
                continue;
            }

            $totalAmount = floatval($payment->TotalAmount ?? 0);
            $amountPaid = floatval($payment->Amount ?? 0);
            $remainingBalance = $totalAmount - $amountPaid;

            $guest = Guest::where('GuestID', $booking->GuestID)->first();

            if (!$guest) {
                $this->warn("⚠️ No guest record found for BookingID: {$booking->BookingID}");
                continue;
            }

            if ($remainingBalance > 0 && !empty($guest->Email)) {
                // ✅ Use the Mailable
                Mail::to($guest->Email)->send(new BookingReminderMail($booking, $payment));

                $daysLeft = $today->diffInDays(Carbon::parse($booking->CheckInDate));
                $this->info("📧 Reminder sent to {$guest->Email} ({$guest->guest_name}) — BookingID: {$booking->BookingID} — ₱" . number_format($remainingBalance, 2) . " remaining — {$daysLeft} day(s) left.");
            } else {
                $reason = empty($guest->Email)
                    ? 'missing email'
                    : 'fully paid';
                $this->info("💰 BookingID {$booking->BookingID} skipped ({$reason}).");
            }
        }

        $this->info('✅ All reminders processed successfully!');
    }
}
