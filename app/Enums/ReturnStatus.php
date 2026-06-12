<?php

namespace App\Enums;

enum ReturnStatus: string
{
    case PENDING     = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED    = 'RESOLVED';
    case REJECTED    = 'REJECTED';
}
