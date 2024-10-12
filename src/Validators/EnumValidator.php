<?php

namespace Alazziaz\LaravelBitmask\Validators;
use InvalidArgumentException;
use UnitEnum;

class EnumValidator
{
    public static function validate(string $enum): void
    {
        if (!is_subclass_of($enum, UnitEnum::class)) {
            throw new InvalidArgumentException('EnumBitmaskHandler enum must be a subclass of UnitEnum');
        }
    }
}
