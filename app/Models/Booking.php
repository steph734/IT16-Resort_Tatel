<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $primaryKey = 'BookingID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'BookingID',
        'GuestID',
        'PackageID',
        'BookingDate',
        'CheckInDate',
        'CheckOutDate',
        'ActualCheckInTime',
        'ActualCheckOutTime',
        'BookingStatus',
        'Pax',
        'NumOfChild',
        'NumOfSeniors',
        'NumOfAdults',
        'ExcessFee',
        'senior_discount',
        'actual_seniors_at_checkout',
    ];

    protected $casts = [
        'BookingDate' => 'datetime',
        'CheckInDate' => 'datetime',
        'CheckOutDate' => 'datetime',
        'ActualCheckInTime' => 'datetime',
        'ActualCheckOutTime' => 'datetime',
        'ExcessFee' => 'decimal:2',
        'senior_discount' => 'decimal:2',
        'actual_seniors_at_checkout' => 'integer',
    ];

    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->BookingID)) {
                $model->BookingID = self::generateBookingID();
            }
        });
    }

    private static function generateBookingID()
    {
        // Get the last booking ordered by the numeric part of BookingID
        $lastBooking = self::orderByRaw('CAST(SUBSTRING(BookingID, 2) AS UNSIGNED) DESC')
            ->first();

        if (!$lastBooking) {
            return 'B001';
        }

        // Extract numeric part (everything after 'B')
        $lastNumber = intval(substr($lastBooking->BookingID, 1));
        $newNumber = $lastNumber + 1;

        return 'B' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function guest()
    {
        return $this->belongsTo(Guest::class, 'GuestID');
    }

    public function payments()
    {
        // Return only active (non-voided) payments by default
        return $this->hasMany(Payment::class, 'BookingID')->where('is_voided', false);
    }

    public function allPayments()
    {
        // Return all payments including voided ones (for audit/history)
        return $this->hasMany(Payment::class, 'BookingID');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'PackageID');
    }

    public function unpaidItems()
    {
        return $this->hasMany(UnpaidItem::class, 'BookingID');
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class, 'BookingID', 'BookingID');
    }
    public function accompanyingGuests()
{
    return $this->hasMany(AccompanyingGuest::class, 'BookigngID', 'BookingID');
}

    // Computed Payment Status
    public function getPaymentStatusAttribute()
    {
        // If package not loaded or not set, mark as Unpaid by default
        if (!$this->package) {
            // If payments exist, still inspect their statuses
            $paymentStatuses = $this->payments->pluck('PaymentStatus')->unique();
            if ($paymentStatuses->contains('For Verification')) {
                return 'For Verification';
            }
            return $this->payments->sum('Amount') > 0 ? 'Downpayment' : 'Unpaid';
        }

        $checkInDate = new \DateTime($this->CheckInDate);
        $checkOutDate = new \DateTime($this->CheckOutDate);
        $days = $checkInDate->diff($checkOutDate)->days;
        $packageTotal = $this->package->Price * $days;
        
        // Use stored ExcessFee instead of recalculating
        $excessFee = $this->ExcessFee ?? 0;
        
        // Apply senior discount if available
        $seniorDiscount = $this->senior_discount ?? 0;
        
        $totalAmount = $packageTotal + $excessFee - $seniorDiscount;

        // Priority-based status: For Verification > Fully Paid > Downpayment > Unpaid
        $paymentStatuses = $this->payments->pluck('PaymentStatus')->unique();

        if ($paymentStatuses->contains('For Verification')) {
            return 'For Verification';
        }

        $totalPaid = $this->payments->sum('Amount');

        // Calculate half amount for partial payment threshold
        $halfAmount = $totalAmount * 0.5;

        // Allow small rounding difference (within 1 peso)
        if ($totalPaid >= ($totalAmount - 1) && $totalAmount > 0) {
            return 'Fully Paid';
        } elseif ($totalPaid > $halfAmount) {
            return 'Partial';
        } elseif ($totalPaid > 0) {
            return 'Downpayment';
        } else {
            return 'Unpaid';
        }
    }
}
