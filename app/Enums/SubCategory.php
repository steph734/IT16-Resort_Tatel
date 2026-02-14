<?php

namespace App\Enums;

enum SubCategory: string
{
    // Resort Supplies
    case HYGIENE_KITS = 'hygiene_kits';
    case CLEANING_MATERIALS = 'cleaning_materials';
    case BEDDINGS = 'beddings';
    case FOOD_SUPPLIES = 'food_supplies';
    case KITCHEN_WARES = 'kitchen_wares';
    case TOILETRIES = 'toiletries';
    case OFFICE_SUPPLIES = 'office_supplies';
    case MAINTENANCE = 'maintenance';
    
    // Rental Items
    case WATER_SPORTS = 'water_sports';
    case RECREATIONAL = 'recreational';
    case FURNITURE = 'furniture';
    case ELECTRONICS = 'electronics';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::HYGIENE_KITS => 'Hygiene Kits',
            self::CLEANING_MATERIALS => 'Cleaning Materials',
            self::BEDDINGS => 'Beddings',
            self::FOOD_SUPPLIES => 'Food Supplies',
            self::KITCHEN_WARES => 'Kitchen Wares',
            self::TOILETRIES => 'Toiletries',
            self::OFFICE_SUPPLIES => 'Office Supplies',
            self::MAINTENANCE => 'Maintenance',
            self::WATER_SPORTS => 'Water Sports',
            self::RECREATIONAL => 'Recreational',
            self::FURNITURE => 'Furniture',
            self::ELECTRONICS => 'Electronics',
            self::OTHER => 'Other',
        };
    }
}
