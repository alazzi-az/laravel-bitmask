<?php

namespace Alazziaz\LaravelBitmask\Handlers;

use Alazziaz\LaravelBitmask\Contracts\Maskable;
use Alazziaz\LaravelBitmask\Util\BitmaskReader;
use Alazziaz\LaravelBitmask\Validators\BitmaskValidator;

final class BitmaskHandler implements Maskable
{
    private BitmaskValidator $validator;

    public function __construct(
        private int $currentMask = 0,
        private readonly ?int $highestBit = null,
    ) {
        $this->validator = new BitmaskValidator($this->highestBit);
        $this->validator->validateMask($this->currentMask);
    }

    public static function create(int $currentMask = 0, ?int $highestBit = null): self
    {
        return new self($currentMask, $highestBit);
    }

    public function __toString(): string
    {
        return (string) $this->currentMask;
    }

    public function toString(): string
    {
        return (new BitmaskReader)->convertToBinaryString($this->currentMask);
    }

    public function getValue(): int
    {
        return $this->currentMask;
    }

    public function add(int ...$bitValues): void
    {
        $this->validateBitValues($bitValues);
        foreach ($bitValues as $bitValue) {
            $this->currentMask |= $bitValue;
        }
    }

    public function delete(int ...$bitValues): void
    {
        foreach ($bitValues as $bitValue) {
            if ($this->has($bitValue)) {
                $this->currentMask &= ~$bitValue;
            }
        }
    }

    public function has(int ...$bitValues): bool
    {
        $this->validateBitValues($bitValues);
        foreach ($bitValues as $bitValue) {
            if (($this->currentMask & $bitValue) !== $bitValue) {
                return false;
            }
        }

        return true;
    }

    private function validateBitValues(array $bitValues): void
    {
        foreach ($bitValues as $bitValue) {
            $this->validator->validateBit($bitValue);
        }
    }
}
