<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'movement_type',
        'quantity',
        'reason',
        'entry_number',
        'rental_id',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'sku', 'sku');
    }

    public function purchaseEntry()
    {
        return $this->belongsTo(PurchaseEntry::class, 'entry_number', 'entry_number');
    }

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');
    }

    // Scopes
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('movement_type', $type);
    }
}
