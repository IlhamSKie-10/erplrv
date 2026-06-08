<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT             = 'DRAFT';
    case CONFIRMED         = 'CONFIRMED';
    case DESIGN_IN_PROGRESS = 'DESIGN_IN_PROGRESS';
    case DESIGN_APPROVED   = 'DESIGN_APPROVED';
    case IN_PRODUCTION     = 'IN_PRODUCTION';
    case READY_TO_SHIP     = 'READY_TO_SHIP';
    case SHIPPED           = 'SHIPPED';
    case COMPLETED         = 'COMPLETED';
    case CANCELLED         = 'CANCELLED';
    case ON_HOLD           = 'ON_HOLD';
}
