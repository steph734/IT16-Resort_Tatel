<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'transaction_type',
        'reference_id',
        'transaction_date',
        'amount',
        'payment_method',
        'payment_status',
        'purpose',
        'booking_id',
        'guest_id',
        'rental_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'processed_by',
        'processor_name',
        'amount_received',
        'change_amount',
        'metadata',
        'reference_number',
        'notes',
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'BookingID');
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'GuestID');
    }

    public function rental()
    {
        return $this->belongsTo(Rental::class, 'rental_id', 'id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'reference_id', 'PaymentID');
    }

    /**
     * The user who processed this transaction (nullable)
     */
    public function processedBy(): BelongsTo
    {
        // Use the existing `processed_by` string column (stores users.user_id) as FK
        return $this->belongsTo(User::class, 'processed_by', 'user_id');
    }

    /**
     * The user who voided this transaction (nullable)
     */
    public function voidedBy(): BelongsTo
    {
        // Use the existing `voided_by` string column (stores users.user_id) as FK
        return $this->belongsTo(User::class, 'voided_by', 'user_id');
    }

    // Scopes
    public function scopeNotVoided($query)
    {
        return $query->where('is_voided', false);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Ensure processor_name is populated when processed_by is set.
     * This helps when code only writes the user id into processed_by —
     * the human name will be filled automatically for display/denormalized use.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!empty($model->processed_by) && empty($model->processor_name)) {
                try {
                    $user = User::where('user_id', $model->processed_by)->first();
                    if ($user) {
                        $model->processor_name = $user->name;
                    }
                } catch (\Throwable $e) {
                    // don't break transaction creation if name lookup fails
                }
            }
        });

        static::updating(function ($model) {
            // If processed_by was changed and processor_name empty, try to populate
            if ($model->isDirty('processed_by') && !empty($model->processed_by) && empty($model->processor_name)) {
                try {
                    $user = User::where('user_id', $model->processed_by)->first();
                    if ($user) {
                        $model->processor_name = $user->name;
                    }
                } catch (\Throwable $e) {
                    // ignore lookup failure
                }
            }
        });
    }
}
