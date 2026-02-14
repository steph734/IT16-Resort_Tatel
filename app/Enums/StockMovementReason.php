<?php

namespace App\Enums;

enum StockMovementReason: string
{
    // IN reasons
    case PO_RECEIPT = 'po_receipt';
    case ADJUSTMENT_IN = 'adjustment_in';
    case RETURN = 'return';
    
    // OUT reasons
    case USAGE = 'usage';
    case RENTAL_DAMAGE = 'rental_damage';
    case LOST = 'lost';
    case EXPIRED = 'expired';
    case ADJUSTMENT_OUT = 'adjustment_out';
    case SOLD = 'sold';

    public function label(): string
    {
        return match($this) {
            self::PO_RECEIPT => 'PO Receipt',
            self::ADJUSTMENT_IN => 'Adjustment (In)',
            self::RETURN => 'Return',
            self::USAGE => 'Usage',
            self::RENTAL_DAMAGE => 'Rental Damage Replacement',
            self::LOST => 'Lost/Stolen',
            self::EXPIRED => 'Expired',
            self::ADJUSTMENT_OUT => 'Adjustment (Out)',
            self::SOLD => 'Sold',
        };
    }

    public function movementType(): StockMovementType
    {
        return match($this) {
            self::PO_RECEIPT, self::ADJUSTMENT_IN, self::RETURN => StockMovementType::IN,
            self::USAGE, self::RENTAL_DAMAGE, self::LOST, self::EXPIRED, self::ADJUSTMENT_OUT, self::SOLD => StockMovementType::OUT,
        };
    }
}
