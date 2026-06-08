<?php

namespace App\Enums;

enum PrintStickerOption: string
{
    case YES            = 'YES';
    case NO             = 'NO';
    case REQUIRED_LATER = 'REQUIRED_LATER';
}
