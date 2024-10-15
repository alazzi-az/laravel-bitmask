<?php

namespace Alazziaz\LaravelBitmask\Casts;


use Alazziaz\Bitmask\Contracts\EnumMaskable;
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use UnitEnum;


class EnumBitmaskCast implements CastsAttributes
{


    public function __construct(protected string $enumClass)
    {

        if (!is_subclass_of($this->enumClass, UnitEnum::class)) {
            throw new InvalidArgumentException("The provided class must be an enum.");
        }


    }


    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return BitmaskFacade::enumBitmaskHandlerFactory()->createWithMask($this->enumClass, $value);
    }


    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof EnumMaskable) {
            return $value->getValue();
        } elseif (is_int($value)) {
            return $value;
        } else {
            return null;
        }
    }
}
