<?php

namespace Workbench\App\Enums;

use Alazziaz\Bitmask\Contracts\MaskableEnum;

enum ArchiveDataFlag : int implements MaskableEnum
{
    case COUNTRIES      = 1 << 0; // 1
    case CITIES         = 1 << 1; // 2
    case FACILITIES     = 1 << 2; // 4
    case NATIONALITIES  = 1 << 3; // 8
    case REGIONS        = 1 << 4; // 16
    case LOCATIONS      = 1 << 5; // 32

    public function toMaskKey(): string
    {
        return match ($this) {
            self::COUNTRIES => 'countries',
            self::CITIES => 'cities',
            self::FACILITIES => 'facilities',
            self::NATIONALITIES => 'nationalities',
            self::REGIONS => 'regions',
            self::LOCATIONS => 'locations',
        };
    }
}
