<?php

namespace App\Enums;

enum AuditAction: string
{
    case SIGN_IN       = 'SIGN_IN';
    case CREATE        = 'CREATE';
    case UPDATE        = 'UPDATE';
    case SUBMIT        = 'SUBMIT';
    case APPROVE       = 'APPROVE';
    case FORWARD       = 'FORWARD';
    case STATUS_CHANGE = 'STATUS_CHANGE';
    case SOFT_DELETE   = 'SOFT_DELETE';
}
