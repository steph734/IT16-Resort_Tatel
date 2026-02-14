<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClosedDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'closed_date',
        'reason',
        'closed_by',
    ];

    protected $casts = [
        'closed_date' => 'date',
    ];

    public static function getClosedDates()
    {
        return self::pluck('closed_date')
            ->map(function($date) {
                return $date instanceof \Carbon\Carbon 
                    ? $date->format('Y-m-d') 
                    : \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
    }

    public static function isDateClosed($date)
    {
        return self::where('closed_date', $date)->exists();
    }

    /**
     * User who closed the date (nullable)
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by', 'user_id');
    }
}
