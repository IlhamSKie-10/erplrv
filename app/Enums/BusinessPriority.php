<?php

namespace App\Enums;

enum BusinessPriority: string
{
    case NORMAL        = 'NORMAL';
    case REPEAT_CLIENT = 'REPEAT_CLIENT';
    case CORPORATE     = 'CORPORATE';
    case VIP           = 'VIP';
    case STRATEGIC     = 'STRATEGIC';
}
