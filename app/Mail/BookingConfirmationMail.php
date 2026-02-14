<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $payment;

    /**
     * Create a new message instance.
     */
    public function __construct($booking, $payment)
    {
        $this->booking = $booking;
        $this->payment = $payment;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Booking Confirmation - JBRB Resort')
                    ->markdown('emails.booking-confirmation')
                    ->with([
                        'booking' => $this->booking,
                        'payment' => $this->payment,
                    ]);
    }
}
