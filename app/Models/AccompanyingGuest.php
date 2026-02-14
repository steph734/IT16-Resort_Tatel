<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccompanyingGuest extends Model
{
    // Accept both legacy (BookingID/AccompanyingID) and snake_case field names
    protected $fillable = [
        'AccompanyingID',
        'BookingID',
        'booking_id',
        'first_name',
        'last_name',
        'gender',
        'guest_type'
    ];
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'BookingID', 'BookingID');
    }
}
