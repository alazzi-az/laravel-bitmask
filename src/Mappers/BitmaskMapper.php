<?php

namespace Alazziaz\LaravelBitmask\Mappers;

use Alazziaz\LaravelBitmask\Util\BitmaskConverter;
use InvalidArgumentException;
use UnitEnum;

class BitmaskMapper
{
    private array $flagMappings = [];

    /** @param class-string<UnitEnum> $enum */
    public function __construct(string $enum)
    {
        foreach ($enum::cases() as $index => $case) {
            $this->flagMappings[$case->name] = (new BitmaskConverter)->indexToBitMask($index);
        }
    }

    public function getBitMask(string $flagName): int
    {
        return $this->flagMappings[$flagName] ?? throw new InvalidArgumentException("Invalid flag name: $flagName");
    }

    public function getAllBitMasks(): array
    {
        return $this->flagMappings;
    }
}
