<?php

namespace Alazziaz\LaravelBitmask\Facades;

use Alazziaz\Bitmask\Contracts\EnumMaskable;
use Alazziaz\Bitmask\Contracts\Maskable;
use Alazziaz\Bitmask\EnumBitmaskFactory;
use Illuminate\Support\Facades\Facade;
use UnitEnum;

/**
 * @method static int indexToBitMask(int $index) Convert an index to its corresponding bitmask value.
 * @method static int bitMaskToIndex(int $mask) Convert a bitmask to its corresponding index.
 * @method static int getEnumMaxBitValue(string $enum) Get the maximum bit value from an enum class.
 * @method static array bitMaskToArray(int $mask) Convert a bitmask to an array of active bits.
 * @method static int arrayToBitMask(array $bits) Convert an array of bits to a bitmask.
 * @method static array getActiveBits(int $bitmask) Retrieve active bits from a bitmask.
 * @method static array getActiveIndexes(int $bitmask) Retrieve active indexes from a bitmask.
 * @method static int countActiveBits(int $bitmask) Count the number of active bits in a bitmask.
 * @method static int getMostSignificantBitIndex(int $bitmask) Get the index of the most significant active bit.
 * @method static int getLeastSignificantBitIndex(int $bitmask) Get the index of the least significant active bit.
 * @method static string convertToBinaryString(int $bitmask) Convert a bitmask to a binary string representation.
 * @method static void validateBit(int $bit) Validate a single bit value.
 * @method static void validateBits(array $bits) Validate an array of bit values.
 * @method static Maskable bitmaskHandler(int $initialMask = 0, ?int $maxBit = null) Create a bitmask handler instance.
 * @method static EnumMaskable enumBitmaskHandler(string $enumClass, UnitEnum ...$bits) Create an enum bitmask handler instance.
 * @method static EnumBitmaskFactory enumBitmaskHandlerFactory() Get a factory instance for creating enum bitmask handlers.
 */
class BitmaskFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bitmask';
    }
}
