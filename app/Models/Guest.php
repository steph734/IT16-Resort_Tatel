<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    use HasFactory;

    protected $primaryKey = 'GuestID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'GuestID',
        'FName',
        'MName',
        'LName',
        'Email',
        'Phone',
        'Address',
        'Contactable',
    ];

    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->GuestID)) {
                $model->GuestID = self::generateGuestID();
            }
        });
    }

    private static function generateGuestID()
    {
        // Get the last guest ordered by the numeric part of GuestID
        $lastGuest = self::orderByRaw('CAST(SUBSTRING(GuestID, 2) AS UNSIGNED) DESC')
            ->first();

        if (!$lastGuest) {
            return 'G001';
        }

        // Extract numeric part (everything after 'G')
        $lastNumber = intval(substr($lastGuest->GuestID, 1));
        $newNumber = $lastNumber + 1;

        return 'G' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'GuestID');
    }

    // Accessor for full name
    public function getGuestNameAttribute()
    {
        $name = $this->FName;
        if ($this->MName) {
            $name .= ' ' . $this->MName;
        }
        $name .= ' ' . $this->LName;
        return $name;
    }
}
