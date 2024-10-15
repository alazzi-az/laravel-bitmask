<?php

namespace Alazziaz\LaravelBitmask\Casts;

use Alazziaz\Bitmask\Contracts\Maskable;
use Alazziaz\Bitmask\Handlers\BitmaskHandler;
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class BitmaskCast implements CastsAttributes
{


    public function __construct(private readonly ?int $maxBit = null)
    {

    }


    public function get(Model $model, string $key, mixed $value, array $attributes): ?BitmaskHandler
    {
        if (is_null($value)) {
            return null;
        }

        if (!is_int($value)) {
            throw new InvalidArgumentException("BitmaskFacade value must be an integer.");
        }

        return BitmaskFacade::bitmaskHandler($value, $this->maxBit);
    }


    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value instanceof Maskable) {
            return $value->getValue();
        }

        if (is_int($value)) {
            return $value;
        }

        return null;
    }
}
