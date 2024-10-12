<?php

namespace Alazziaz\LaravelBitmask\Util;

use Alazziaz\LaravelBitmask\Validators\BitmaskValidator;
use OutOfRangeException;

class BitmaskConverter
{
    public function indexToBitMask(int $index): int
    {
        if ($index < 0) {
            throw new OutOfRangeException("Index cannot be negative: {$index}");
        }
        return 1 << $index;
    }

    public function bitMaskToIndex(int $mask): int
    {
        (new BitmaskValidator())->ensureSingleBitIsSet($mask);
        return (new BitmaskReader())->getMostSignificantBitIndex($mask);
    }
}
