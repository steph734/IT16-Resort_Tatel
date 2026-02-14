<?php

namespace App\Enums;

enum RentalStatus: string
{
    case ISSUED = 'Issued';
    case RETURNED = 'Returned';
    case DAMAGED = 'Damaged';
    case LOST = 'Lost';

    public function label(): string
    {
        return match ($this) {
            self::ISSUED => 'Issued',
            self::RETURNED => 'Returned',
            self::DAMAGED => 'Damaged',
            self::LOST => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ISSUED => 'warning',
            self::RETURNED => 'success',
            self::DAMAGED => 'danger',
            self::LOST => 'danger',
        };
    }
}
