<?php

namespace App\Enums;

enum PriorityTier: string
{
    case TIER_1_OVERDUE = 'TIER_1_OVERDUE';
    case TIER_2_TODAY   = 'TIER_2_TODAY';
    case TIER_3_H3      = 'TIER_3_H3';
    case TIER_4_SAFE    = 'TIER_4_SAFE';
    case TIER_5_DONE    = 'TIER_5_DONE';
}
