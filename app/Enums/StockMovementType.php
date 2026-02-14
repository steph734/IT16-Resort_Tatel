<?php

namespace App\Enums;

enum StockMovementType: string
{
    case IN = 'in';
    case OUT = 'out';

    public function label(): string
    {
        return match($this) {
            self::IN => 'Stock In',
            self::OUT => 'Stock Out',
        };
    }
}
