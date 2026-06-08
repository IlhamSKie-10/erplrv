<?php

namespace App\Enums;

enum RoleCode: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case CS          = 'CS';
    case DESIGNER    = 'DESIGNER';
    case PRODUCTION  = 'PRODUCTION';
    case MANAGER     = 'MANAGER';
    case DEVELOPER   = 'DEVELOPER';
}
