<?php

use Alazziaz\LaravelBitmask\Contracts\MaskableEnum;

it('returns the correct mask key from toMaskKey', function () {

    $maskableEnum = new class implements MaskableEnum {
        public function toMaskKey(): string
        {
            return 'example_key';
        }
    };

    expect($maskableEnum->toMaskKey())->toBe('example_key');
});
