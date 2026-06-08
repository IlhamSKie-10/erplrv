<?php

namespace App\Enums;

enum ReminderStatus: string
{
    case PENDING      = 'PENDING';
    case ACKNOWLEDGED = 'ACKNOWLEDGED';
    case DONE         = 'DONE';
    case CANCELLED    = 'CANCELLED';
}
