<?php

namespace App\Enums;

enum BlockedReason: string
{
    case WAITING_MATERIAL          = 'WAITING_MATERIAL';
    case WAITING_DESIGN            = 'WAITING_DESIGN';
    case MACHINE_ISSUE             = 'MACHINE_ISSUE';
    case MANPOWER_ISSUE            = 'MANPOWER_ISSUE';
    case CUSTOMER_REVISION         = 'CUSTOMER_REVISION';
    case PREVIOUS_STAGE_INCOMPLETE = 'PREVIOUS_STAGE_INCOMPLETE';
    case OTHER                     = 'OTHER';
}
