<?php

use Alazziaz\LaravelBitmask\Contracts\Maskable;

it('returns the correct value from getValue', function () {
    $mockValue = 42;
    $maskable = new class($mockValue) implements Maskable
    {
        private int $value;

        public function __construct(int $value)
        {
            $this->value = $value;
        }

        public function getValue(): int
        {
            return $this->value;
        }
    };

    expect($maskable->getValue())->toBe($mockValue);
});
