<?php

namespace App\Enums;

enum RateType: string
{
    case PER_DAY = 'Per-Day';
    case FLAT = 'Flat';

    public function label(): string
    {
        return match($this) {
            self::PER_DAY => 'Per Day',
            self::FLAT => 'Flat Rate',
        };
    }
}
