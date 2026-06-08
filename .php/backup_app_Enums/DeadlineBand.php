<?php

namespace App\Enums;

enum DeadlineBand: string
{
    case SAFE      = 'SAFE';
    case H3        = 'H3';
    case DUE_TODAY = 'DUE_TODAY';
    case OVERDUE   = 'OVERDUE';
    case DONE      = 'DONE';
}
