<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'display_order'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    /**
     * Packages that belong to this amenity
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'amenity_package', 'amenity_id', 'package_id')
            ->withTimestamps();
    }
}
