<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Package extends Model
{
    use HasFactory;

    protected $table = 'packages';
    protected $primaryKey = 'PackageID';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'PackageID',
        'Name',
        'Description',
        'Price',
        'max_guests',
        'excess_rate',
    ];

    public $timestamps = true;

    protected $appends = ['amenities_array'];

    public function getAmenitiesArrayAttribute()
    {
        // Prefer the relation (DB) if it's loaded or exists
        if ($this->relationLoaded('amenities')) {
            return $this->amenities->pluck('name')->toArray();
        }

        if ($this->exists && $this->amenities()->exists()) {
            return $this->amenities()->pluck('name')->toArray();
        }

        // Fallback: parse the Description field (legacy)
        if (empty($this->Description)) {
            return [];
        }

        // Parse the Description field to extract amenities
        // Assuming Description is stored as JSON array or newline-separated
        if ($this->isJson($this->Description)) {
            return json_decode($this->Description, true);
        }

        // Split by newlines and filter out empty lines
        $amenities = array_filter(
            array_map('trim', explode("\n", $this->Description)),
            function ($item) {
                return !empty($item);
            }
        );

        return array_values($amenities);
    }
    
    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->PackageID)) {
                $model->PackageID = self::generatePackageID();
            }
        });
    }

    private static function generatePackageID()
    {
        $lastPackage = self::orderBy('PackageID', 'desc')->first();
        
        if (!$lastPackage) {
            return 'PK001';
        }
        
        $lastNumber = intval(substr($lastPackage->PackageID, 2));
        $newNumber = $lastNumber + 1;
        
        return 'PK' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'PackageID');
    }

    /**
     * Many-to-many relation to amenities
     * pivot table: amenity_package (package_id, amenity_id)
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_package', 'package_id', 'amenity_id')
            ->withTimestamps();
    }
}
