<?php

namespace App\Enums;

enum ItemCondition: string
{
    case GOOD = 'Good';
    case DAMAGED = 'Damaged';
    case LOST = 'Lost';

    public function label(): string
    {
        return match($this) {
            self::GOOD => 'Good Condition',
            self::DAMAGED => 'Damaged',
            self::LOST => 'Lost',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::GOOD => 'success',
            self::DAMAGED => 'warning',
            self::LOST => 'danger',
        };
    }
}
