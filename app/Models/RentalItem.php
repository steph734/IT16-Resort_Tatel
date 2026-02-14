<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'rate_type',
        'rate',
        'description',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    /**
     * Get the inventory item this rental item links to
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'sku', 'sku');
    }

    /**
     * Get all rentals for this item
     */
    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }

    /**
     * Get currently issued rentals
     */
    public function issuedRentals()
    {
        return $this->hasMany(Rental::class)->where('status', 'Issued');
    }

    /**
     * Check if item is available
     */
    public function isAvailable($quantity = 1)
    {
        $issuedQty = $this->issuedRentals()->sum('quantity');
        $availableQty = $this->inventoryItem->quantity_on_hand - $issuedQty;
        return $availableQty >= $quantity;
    }

    /**
     * Get available quantity
     */
    public function getAvailableQuantity()
    {
        $issuedQty = $this->issuedRentals()->sum('quantity');
        return max(0, $this->inventoryItem->quantity_on_hand - $issuedQty);
    }

    /**
     * Get name from inventory item
     */
    public function getNameAttribute()
    {
        return $this->inventoryItem ? $this->inventoryItem->name : 'N/A';
    }

    /**
     * Get code/SKU from inventory item
     */
    public function getCodeAttribute()
    {
        return $this->inventoryItem ? $this->inventoryItem->sku : 'N/A';
    }

    /**
     * Get stock on hand from inventory item
     */
    public function getStockOnHandAttribute()
    {
        return $this->inventoryItem ? $this->inventoryItem->quantity_on_hand : 0;
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }
}
