<?php

namespace App\Enums;

enum ProductionStageCode: string
{
    case LAS      = 'LAS';
    case LASER    = 'LASER';
    case RANGKAI  = 'RANGKAI';
    case STCR_UV  = 'STCR_UV';
    case CD       = 'CD';
    case FINISHING = 'FINISHING';
    case BUBBLE   = 'BUBBLE';
    case DATE     = 'DATE';
}
