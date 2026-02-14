<?php

namespace App\Enums;

enum FeeType: string
{
    case RENTAL = 'Rental';
    case ADJUSTMENT = 'Adjustment';
    case DAMAGE = 'Damage';
    case LOSS = 'Loss';

    public function label(): string
    {
        return match($this) {
            self::RENTAL => 'Rental Fee',
            self::ADJUSTMENT => 'Adjustment',
            self::DAMAGE => 'Damage Fee',
            self::LOSS => 'Loss Fee',
        };
    }
}
