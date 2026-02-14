<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnpaidItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'ItemID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'ItemID',
        'BookingID',
        'ItemName',
        'Quantity',
        'Price',
        'TotalAmount',
        'IsPaid',
    ];

    protected $casts = [
        'Quantity' => 'integer',
        'Price' => 'decimal:2',
        'TotalAmount' => 'decimal:2',
        'IsPaid' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->ItemID)) {
                $model->ItemID = self::generateItemID();
            }
            
            // Auto-calculate total amount
            $model->TotalAmount = $model->Quantity * $model->Price;
        });

        static::updating(function ($model) {
            // Auto-calculate total amount on update
            $model->TotalAmount = $model->Quantity * $model->Price;
        });
    }

    private static function generateItemID()
    {
        $lastItem = self::orderBy('ItemID', 'desc')->first();

        if (!$lastItem) {
            return 'UI001';
        }

        $lastNumber = intval(substr($lastItem->ItemID, 2));
        $newNumber = $lastNumber + 1;

        return 'UI' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationship
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'BookingID');
    }
}
