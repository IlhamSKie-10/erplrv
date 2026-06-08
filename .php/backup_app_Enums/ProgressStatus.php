<?php

namespace App\Enums;

enum ProgressStatus: string
{
    case NOT_STARTED = 'NOT_STARTED';
    case STARTED     = 'STARTED';
    case COMPLETED   = 'COMPLETED';
    case BLOCKED     = 'BLOCKED';
    case REWORK      = 'REWORK';
    case DONE        = 'DONE';
}
