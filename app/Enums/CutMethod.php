<?php

namespace App\Enums;

enum CutMethod: string
{
    case NONE      = 'NONE';
    case CNC       = 'CNC';
    case LASER     = 'LASER';
    case OUTSOURCE = 'OUTSOURCE';
}
