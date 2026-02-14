<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case RECEIVED = 'received';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::ISSUED => 'Issued',
            self::RECEIVED => 'Received',
            self::CLOSED => 'Closed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::ISSUED => 'blue',
            self::RECEIVED => 'green',
            self::CLOSED => 'slate',
        };
    }
}
