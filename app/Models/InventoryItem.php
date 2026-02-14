<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    // Use SKU as primary key
    protected $primaryKey = 'sku';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sku',
        'name',
        'category',
        'description',
        'quantity_on_hand',
        'reorder_level',
        'average_cost',
        'unit_of_measure',
        'is_active',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'reorder_level' => 'integer',
        'average_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['last_purchase_date'];

    // Relationships
    public function purchaseEntryItems()
    {
        return $this->hasMany(PurchaseEntryItem::class, 'sku', 'sku');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'sku', 'sku');
    }

    /**
     * Get the rental item configuration for this inventory item
     */
    public function rentalItem()
    {
        return $this->hasOne(RentalItem::class, 'sku', 'sku');
    }

    // Accessors & Helpers
    
    /**
     * Get the last purchase date for this item
     */
    public function getLastPurchaseDateAttribute()
    {
        $lastPurchase = $this->purchaseEntryItems()
            ->with('purchaseEntry')
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($lastPurchase && $lastPurchase->purchaseEntry) {
            return \Carbon\Carbon::parse($lastPurchase->purchaseEntry->purchase_date);
        }
        
        return null;
    }
    
    public function isLowStock(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->quantity_on_hand <= 0) {
            return 'out-of-stock';
        } elseif ($this->isLowStock()) {
            return 'low-stock';
        } else {
            return 'in-stock';
        }
    }

    public function isRentalItem(): bool
    {
        return $this->category === 'rental_item';
    }

    public function isResortSupply(): bool
    {
        return $this->category === 'resort_supply';
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRentalItems(Builder $query): Builder
    {
        return $query->where('category', 'rental_item');
    }

    public function scopeResortSupplies(Builder $query): Builder
    {
        return $query->where('category', 'resort_supply');
    }

    /**
     * Generate SKU based on category
     * Format: XXX-000 where XXX is category prefix and 000 is sequential number
     */
    public static function generateSKU(string $category): string
    {
        // Map categories to 3-letter prefixes
        $prefixes = [
            'cleaning' => 'CLN',
            'kitchen' => 'KTC',
            'amenity' => 'AMN',
            'rental_item' => 'RNT',
        ];

        $prefix = $prefixes[$category] ?? 'ITM';

        // Get the last item in this category
        $lastItem = self::where('category', $category)
            ->where('sku', 'like', "{$prefix}-%")
            ->orderBy('sku', 'desc')
            ->first();

        if ($lastItem && $lastItem->sku) {
            // Extract number from XXX-000 format
            $lastNumber = (int) substr($lastItem->sku, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$prefix}-{$newNumber}";
    }
}
