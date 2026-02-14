<?php

namespace App\Enums;

enum ItemCategory: string
{
    case CLEANING = 'cleaning';
    case KITCHEN = 'kitchen';
    case AMENITY = 'amenity';
    case RENTAL_ITEM = 'rental_item';

    public function label(): string
    {
        return match($this) {
            self::CLEANING => 'Cleaning',
            self::KITCHEN => 'Kitchen',
            self::AMENITY => 'Amenity',
            self::RENTAL_ITEM => 'Rental Item',
        };
    }
}
