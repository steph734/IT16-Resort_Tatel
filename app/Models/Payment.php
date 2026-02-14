<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Enums\PaymentMethod;

class Payment extends Model
{
    use HasFactory;

    protected $primaryKey = 'PaymentID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'PaymentID',
        'BookingID',
        'PaymentDate',
        'Amount',
        'TotalAmount',
        'PaymentMethod',
        'PaymentStatus',
        'PaymentPurpose',
    // Payment proof image column removed from schema
        'ReferenceNumber',
        'NameOnAccount',
        'AccountNumber',
        'total_outstanding',
        'amount_received',
        'change_amount',
        'processed_by',
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    public $timestamps = true;

    protected $casts = [
        'Amount' => 'decimal:2',
        'TotalAmount' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->PaymentID)) {
                $model->PaymentID = self::generatePaymentID();
            }
        });
    }

    private static function generatePaymentID()
    {
        // Start with the highest numeric value found
        $lastPayment = self::orderBy('PaymentID', 'desc')->first();
        $startNumber = $lastPayment ? intval(substr($lastPayment->PaymentID, 2)) : 0;
        
        // Keep incrementing until we find an unused PaymentID
        // This prevents reusing voided payment IDs
        $attempts = 0;
        $maxAttempts = 1000; // Safety limit
        
        do {
            $newNumber = $startNumber + 1 + $attempts;
            $newId = 'PY' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            
            // Check if this ID already exists (could be voided)
            $exists = self::where('PaymentID', $newId)->exists();
            
            $attempts++;
            
            if ($attempts >= $maxAttempts) {
                throw new \Exception('Could not generate unique PaymentID after ' . $maxAttempts . ' attempts');
            }
            
        } while ($exists);

        return $newId;
    }

    // Relationship
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'BookingID', 'BookingID');
    }

    /**
     * Scope to get only active (non-voided) payments
     */
    public function scopeActive($query)
    {
        return $query->where('is_voided', false);
    }

    /**
     * Scope to get only voided payments
     */
    public function scopeVoided($query)
    {
        return $query->where('is_voided', true);
    }
}
