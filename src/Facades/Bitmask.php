<?php

namespace Alazziaz\LaravelBitmask\Facades;

use Alazziaz\LaravelBitmask\Util\BitmaskConverter;
use Alazziaz\LaravelBitmask\Util\BitmaskReader;
use Alazziaz\LaravelBitmask\Validators\BitmaskValidator;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Alazziaz\LaravelBitmask\LaravelBitmask
 *
 * @method static BitmaskReader reader() Get the BitmaskReader instance.
 * @method static BitmaskValidator validator() Get the BitmaskValidator instance.
 * @method static BitmaskConverter converter() Get the BitmaskConverter instance.
 * @method static array getActiveBits(int $bitmask) Get an array of active bits from the provided bitmask.
 * @method static int getMostSignificantBitIndex(int $bitmask) Get the index of the most significant bit set in the provided bitmask.
 * @method static string convertToBinaryString(int $bitmask) Convert the provided bitmask to a binary string representation.
 * @method static int indexToBitMask(int $index) Convert an index to its corresponding bitmask value.
 * @method static int bitMaskToIndex(int $mask) Convert a bitmask to its corresponding index.
 * @method static void validateBit(int $bit) Validate that the provided bit is valid.
 * @method static void validateMask(int $mask) Validate that the provided mask is valid.
 * @method static bool isOnlyOneBitSet(int $mask) Check if only one bit is set in the provided mask.
 * @method static void ensureSingleBitIsSet(int $bitmask) Ensure that only a single bit is set in the provided bitmask.
 */
class Bitmask extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bitmask';
    }
}
