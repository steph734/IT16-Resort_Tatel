<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseEntryItem extends Model
{
    use HasFactory;

    // Composite primary key
    protected $primaryKey = ['entry_number', 'sku'];
    public $incrementing = false;

    protected $fillable = [
        'entry_number',
        'sku',
        'item_name',
        'quantity',
        'unit_cost',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Override getKey for composite keys
    public function getKey()
    {
        return implode('_', [
            $this->getAttribute('entry_number'),
            $this->getAttribute('sku')
        ]);
    }

    // Relationships
    public function purchaseEntry()
    {
        return $this->belongsTo(PurchaseEntry::class, 'entry_number', 'entry_number');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'sku', 'sku');
    }

    // Auto-calculate subtotal before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->quantity * $item->unit_cost;
        });
    }
}
