<?php

namespace Alazziaz\LaravelBitmask\Handlers;

use Alazziaz\LaravelBitmask\Contracts\Maskable;
use Alazziaz\LaravelBitmask\Contracts\MaskableEnum;
use Alazziaz\LaravelBitmask\Mappers\BitmaskMapper;
use Alazziaz\LaravelBitmask\Validators\EnumValidator;
use Alazziaz\LaravelBitmask\Validators\MaskEnumValidator;
use UnitEnum;

final readonly class EnumBitmaskHandler implements Maskable
{
    public function __construct(
        /** @var UnitEnum */
        private string $enum,
        private Maskable $maskHandler,
        private BitmaskMapper $maskMapper

    ) {

        EnumValidator::validate($this->enum);
    }

    public static function none(string $enum): self
    {
        return self::create($enum);
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     */
    public static function create(string $enum, UnitEnum ...$bits): self
    {
        $currentMask = array_reduce($bits, fn ($mask, $bit) => $mask | $bit->value, 0);

        return self::createWithMaskInternal($enum, $currentMask);
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     */
    private static function createWithMaskInternal(string $enum, int $mask): self
    {
        return new self(
            $enum,
            new BitmaskHandler($mask, count($enum::cases()) - 1),
            new BitmaskMapper($enum)
        );
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     */
    public static function createWithMask(string $enum, int $mask): self
    {
        return self::createWithMaskInternal($enum, $mask);
    }

    public static function without(string $enum, UnitEnum ...$bits): self
    {
        return self::all($enum)->delete(...$bits);
    }

    public function delete(UnitEnum ...$bits): self
    {
        MaskEnumValidator::validate($this->enum, $bits);
        $this->maskHandler->delete(...$this->enumToInt(...$bits));

        return $this;
    }

    private function enumToInt(UnitEnum ...$bits): array
    {
        return array_map(fn (UnitEnum $bit) => $this->maskMapper->getBitMask($bit->name), $bits);
    }

    /**
     * @param  class-string<UnitEnum>  $enum
     */
    public static function all(string $enum): self
    {

        return self::create($enum, ...$enum::cases());
    }

    public function add(UnitEnum ...$bits): self
    {
        MaskEnumValidator::validate($this->enum, $bits);
        $this->maskHandler->add(...$this->enumToInt(...$bits));

        return $this;
    }

    public function getValue(): int
    {
        return $this->maskHandler->getValue();
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->enum::cases() as $mask) {
            if ($mask instanceof UnitEnum) {
                $key = $mask instanceof MaskableEnum ? $mask->toMaskKey() : strtolower($mask->name);
                $result[$key] = $this->has($mask);
            }
        }

        return $result;
    }

    public function has(UnitEnum ...$bits): bool
    {
        MaskEnumValidator::validate($this->enum, $bits);

        return $this->maskHandler->has(...$this->enumToInt(...$bits));
    }
}
