<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case UNPAID = 'UNPAID';
    case DP     = 'DP';
    case LUNAS  = 'LUNAS';
}
