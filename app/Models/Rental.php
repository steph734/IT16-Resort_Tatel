<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'BookingID',
        'rental_item_id',
        'quantity',
        'rate_snapshot',
        'rate_type_snapshot',
        'status',
        'returned_quantity',
        'condition',
        'notes',
        'damage_description',
        'issued_at',
        'returned_at',
        'issued_by',
        'returned_by',
        'is_paid',
    ];

    protected $casts = [
        'rate_snapshot' => 'decimal:2',
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
        'issued_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    /**
     * Get the booking for this rental
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'BookingID', 'BookingID');
    }

    /**
     * Get the rental item
     */
    public function rentalItem()
    {
        return $this->belongsTo(RentalItem::class);
    }

    /**
     * Get all fees for this rental
     */
    public function fees()
    {
        return $this->hasMany(RentalFee::class, 'rental_id');
    }

    /**
     * Get the user who issued this rental
     */
    public function issuedByUser()
    {
        return $this->belongsTo(User::class, 'issued_by', 'user_id');
    }

    /**
     * Get the user who processed the return
     */
    public function returnedByUser()
    {
        return $this->belongsTo(User::class, 'returned_by', 'user_id');
    }

    /**
     * Calculate total rental charges
     */
    public function calculateTotalCharges()
    {
        $rentalFee = 0;

        if ($this->rate_type_snapshot === 'Per-Day') {
            // Calculate days between issued and returned (or current date if not returned)
            $endDate = $this->returned_at ?? Carbon::now();
            $days = $this->issued_at->diffInDays($endDate);
            $days = max(1, $days); // Minimum 1 day
            $rentalFee = $this->rate_snapshot * $days * $this->quantity;
        } else {
            // Flat rate
            $rentalFee = $this->rate_snapshot * $this->quantity;
        }

        // Add all additional fees (damage, loss, adjustments)
        $additionalFees = $this->fees()->sum('amount');

        return $rentalFee + $additionalFees;
    }

    /**
     * Get rental fee breakdown
     */
    public function getFeeBreakdown()
    {
        $rentalFee = 0;
        $days = 0;

        if ($this->rate_type_snapshot === 'Per-Day') {
            $endDate = $this->returned_at ?? Carbon::now();
            $days = $this->issued_at->diffInDays($endDate);
            $days = max(1, $days);
            $rentalFee = $this->rate_snapshot * $days * $this->quantity;
        } else {
            $rentalFee = $this->rate_snapshot * $this->quantity;
        }

        return [
            'rental_fee' => $rentalFee,
            'days' => $days,
            'additional_fees' => $this->fees,
            'total' => $this->calculateTotalCharges(),
        ];
    }
}
