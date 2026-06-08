<?php

namespace App\Enums;

enum PaymentType: string
{
    case SPL     = 'SPL';
    case COD     = 'COD';
    case NON_COD = 'NON_COD';
}
