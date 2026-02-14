<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'type',
        'amount',
        'reason',
        'photo_path',
        'added_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the rental this fee belongs to
     */
    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Get the user who added this fee
     */
    public function addedByUser()
    {
        return $this->belongsTo(User::class, 'added_by', 'user_id');
    }
}
