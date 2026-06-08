<?php

namespace App\Enums;

enum NotificationLevel: string
{
    case INFO     = 'INFO';
    case WARNING  = 'WARNING';
    case CRITICAL = 'CRITICAL';
}
