<?php

namespace Alazziaz\LaravelBitmask\Contracts;

interface MaskableEnum
{
    public function toMaskKey(): string;
}
