<?php

namespace Alazziaz\LaravelBitmask;

use Alazziaz\LaravelBitmask\Util\BitmaskConverter;
use Alazziaz\LaravelBitmask\Util\BitmaskReader;
use Alazziaz\LaravelBitmask\Validators\BitmaskValidator;

class LaravelBitmask {
    public function __construct(protected BitmaskReader $reader, protected BitmaskValidator $validator, protected BitmaskConverter $converter)
    {

    }
    public function reader(): BitmaskReader
    {
        return $this->reader;
    }
    public function validator(): BitmaskValidator
    {
        return $this->validator;
    }
    public function converter(): BitmaskConverter
    {
        return $this->converter;
    }
    public function getActiveBits(int $bitmask): array
    {
        return $this->reader->getActiveBits($bitmask);
    }

    public function getMostSignificantBitIndex(int $bitmask): int
    {
        return $this->reader->getMostSignificantBitIndex($bitmask);
    }

    public function convertToBinaryString(int $bitmask): string
    {
        return $this->reader->convertToBinaryString($bitmask);
    }

    public function indexToBitMask(int $index): int
    {
        return $this->converter->indexToBitMask($index);
    }

    public function bitMaskToIndex(int $mask): int
    {
        return $this->converter->bitMaskToIndex($mask);
    }

    public function validateBit(int $bit): void
    {
        $this->validator->validateBit($bit);
    }

    public function validateMask(int $mask): void
    {
        $this->validator->validateMask($mask);
    }


    public function isOnlyOneBitSet(int $mask): bool
    {
        return $this->validator->isOnlyOneBitSet($mask);
    }

    public function ensureSingleBitIsSet(int $bitmask): void
    {
        $this->validator->ensureSingleBitIsSet($bitmask);
    }
}
