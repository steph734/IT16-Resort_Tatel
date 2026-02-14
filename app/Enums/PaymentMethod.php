<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case GCASH = 'gcash';
    case TYME = 'tyme';
    case BDO = 'bdo';
    case BPI = 'bpi';
}
