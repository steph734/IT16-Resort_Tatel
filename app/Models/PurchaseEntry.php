<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseEntry extends Model
{
    use HasFactory;

    // Use entry_number as primary key
    protected $primaryKey = 'entry_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'entry_number',
        'purchase_date',
        'total_amount',
        'vendor_name',
        'receipt_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(PurchaseEntryItem::class, 'entry_number', 'entry_number');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'entry_number', 'entry_number');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Helper method to generate entry number
    public static function generateEntryNumber()
    {
        $lastEntry = self::orderBy('entry_number', 'desc')->first();

        if ($lastEntry) {
            // Extract number from PE000 format
            $lastNumber = (int) substr($lastEntry->entry_number, 2);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "PE{$newNumber}";
    }
}
