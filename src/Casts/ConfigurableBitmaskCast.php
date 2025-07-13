<?php

namespace Alazziaz\LaravelBitmask\Casts;

use Alazziaz\LaravelBitmask\Contracts\ConfigurableBitmaskContract;
use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use UnitEnum;

abstract class ConfigurableBitmaskCast implements CastsAttributes, ConfigurableBitmaskContract
{
	/** @var BitmaskStateConfiguration|null */
	private static ?BitmaskStateConfiguration $cachedConfig = null;

	public function get(Model $model, string $key, mixed $value, array $attributes): ?ConfigurableBitmaskHandler
	{
		if (is_null($value)) {
			return null;
		}

		if (!is_int($value)) {
			throw new InvalidArgumentException('Bitmask value must be an integer.');
		}

		return new ConfigurableBitmaskHandler($value, static::config());
	}

	public function set(Model $model, string $key, mixed $value, array $attributes): ?int
	{
		if ($value instanceof ConfigurableBitmaskHandler) {
			return $value->getValue();
		}

		if (is_int($value)) {
			// Validate the transition if we have a previous value
			$previousValue = $attributes[$key] ?? 0;
			if ($previousValue !== $value) {
				// Skip validation only for new models with no previous value (initial creation)
				// This allows setting any initial state during model creation
				if (!$model->exists && $previousValue === 0) {
					return $value;
				}

				$handler = new ConfigurableBitmaskHandler($previousValue, static::config());

				// Check if the transition is allowed
				if (!$handler->canTransitionTo($value)) {
					throw new InvalidArgumentException(
						"Transition from {$previousValue} to {$value} is not allowed by the state machine configuration."
					);
				}
			}

			return $value;
		}

		if ($value instanceof UnitEnum || $value instanceof BackedEnum) {
			$intValue = $this->convertEnumToInt($value);

			// Same validation logic for enum values
			$previousValue = $attributes[$key] ?? 0;
			if ($previousValue !== $intValue) {
				// Skip validation only for new models with no previous value (initial creation)
				if (!$model->exists && $previousValue === 0) {
					return $intValue;
				}

				$handler = new ConfigurableBitmaskHandler($previousValue, static::config());

				if (!$handler->canTransitionTo($intValue)) {
					throw new InvalidArgumentException(
						"Transition from {$previousValue} to {$intValue} is not allowed by the state machine configuration."
					);
				}
			}

			return $intValue;
		}

		return null;
	}

	/**
	 * Get the cached configuration to avoid recreating it multiple times.
	 */
	protected static function getCachedConfig(): BitmaskStateConfiguration
	{
		if (self::$cachedConfig === null) {
			self::$cachedConfig = static::config();
		}

		return self::$cachedConfig;
	}

	/**
	 * Convert enum to integer value.
	 */
	private function convertEnumToInt(UnitEnum|BackedEnum $enum): int
	{
		if ($enum instanceof BackedEnum) {
			return $enum->value;
		}

		// For UnitEnum, use ordinal position with bit shifting
		$cases = $enum::cases();
		$index = array_search($enum, $cases, true);
		return 1 << $index;
	}

	/**
	 * Implementation of ConfigurableBitmaskContract methods
	 * These are implemented as static methods that work with the configuration
	 */
	public function canTransition(mixed $targetState): bool
	{
		return false; // This will be overridden by ConfigurableBitmaskHandler
	}

	public function getActiveStates(): array
	{
		return []; // This will be overridden by ConfigurableBitmaskHandler
	}

	public function getPossibleTransitions(): array
	{
		return []; // This will be overridden by ConfigurableBitmaskHandler
	}

	public function validateTransition(mixed $targetState): void
	{
		// This will be overridden by ConfigurableBitmaskHandler
	}

	/**
	 * Abstract method that must be implemented by concrete casts
	 * to define their state machine configuration.
	 */
	abstract public static function config(): BitmaskStateConfiguration;
}
