<?php

namespace Alazziaz\LaravelBitmask\Validators;



use Alazziaz\LaravelBitmask\Util\BitmaskConverter;
use Alazziaz\LaravelBitmask\Util\BitmaskReader;
use InvalidArgumentException;
use OutOfRangeException;

readonly class BitmaskValidator
{
    public function __construct(private ?int $maxBit = null)
    {

    }

    public function validateBit(int $bit): void
    {
        $this->validateMask($bit);
        if (!$this->isOnlyOneBitSet($bit)) {
            throw new InvalidArgumentException("Provided value {$bit} is not a single bit.");
        }
    }

    public function validateMask(int $mask): void
    {
        if ($mask < 0 || $this->isOutOfRange($mask)) {
            throw new OutOfRangeException("Mask value {$mask} is out of range.");
        }
    }

    public function isOutOfRange(int $mask): bool
    {
        return (null !== $this->maxBit && $mask >= (new BitmaskConverter())->indexToBitMask($this->maxBit + 1));
    }

    public function isOnlyOneBitSet(int $mask): bool
    {
        return (1 << (new BitmaskReader())->getMostSignificantBitIndex($mask)) === $mask;
    }

    public function ensureSingleBitIsSet(int $bitmask): void
    {
        if (!$this->isOnlyOneBitSet($bitmask)) {
            throw new InvalidArgumentException('The provided argument must represent a single set bit.');
        }
    }
}
