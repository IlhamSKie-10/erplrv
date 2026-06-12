<?php

namespace App\Enums;

enum ReturnResolution: string
{
    case REWORK      = 'REWORK';
    case REPLACEMENT = 'REPLACEMENT';
    case REFUND      = 'REFUND';
}
