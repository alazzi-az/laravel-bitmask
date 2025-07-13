<?php

namespace Alazziaz\LaravelBitmask\Traits;

use Alazziaz\LaravelBitmask\Casts\ConfigurableBitmaskCast;
use Alazziaz\LaravelBitmask\StateMachine\ConfigurableBitmaskHandler;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use UnitEnum;

/**
 * Trait HasBitmask
 *
 * Provides Eloquent query scopes for bitmask operations on multiple columns.
 * Now includes full support for configurable bitmask state machines.
 *
 * @method static Builder whereHasFlag(string $column, int|BackedEnum $flag)
 * @method static Builder whereHasAnyFlags(string $column, array $flags)
 * @method static Builder whereHasAllFlags(string $column, array $flags)
 * @method static Builder whereHasNoFlag(string $column, int|BackedEnum $flag)
 * @method static Builder whereCanTransitionTo(string $column, int|BackedEnum|UnitEnum $targetState)
 * @method static Builder whereAtState(string $column, int|BackedEnum|UnitEnum $state)
 * @method static Builder whereInStates(string $column, array $states)
 * @method static Builder whereHasAllStates(string $column, array $states)
 * @method static Builder whereHasAnyStates(string $column, array $states)
 */
trait HasBitmask
{
    /**
     * Scope a query to include records where a specific flag is set on a specified column.
     *
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasFlag(Builder $query, string $column, int|BackedEnum $flag): Builder
    {
        $this->validateColumn($column);
        $bitmaskValue = $this->getFlagValue($column, $flag);

        return $query->whereRaw("({$column} & ?) = ?", [$bitmaskValue, $bitmaskValue]);
    }

    /**
     * Scope a query to include records where any of the specified flags are set on a specified column.
     *
     * @param array<int|BackedEnum> $flags
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasAnyFlags(Builder $query, string $column, array $flags): Builder
    {
        $this->validateColumn($column);
        $this->validateFlags($flags);
        $mask = $this->calculateMask($column, $flags);

        return $query->whereRaw("{$column} & ? <> 0", [$mask]);
    }

    /**
     * Scope a query to include records where all of the specified flags are set on a specified column.
     *
     * @param array<int|BackedEnum> $flags
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasAllFlags(Builder $query, string $column, array $flags): Builder
    {
        $this->validateColumn($column);
        $this->validateFlags($flags);
        $mask = $this->calculateMask($column, $flags);

        return $query->whereRaw("{$column} & ? = ?", [$mask, $mask]);
    }

    /**
     * Scope a query to include records where a specific flag is NOT set on a specified column.
     *
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasNoFlag(Builder $query, string $column, int|BackedEnum $flag): Builder
    {
        $this->validateColumn($column);
        $bitmaskValue = $this->getFlagValue($column, $flag);

        return $query->whereRaw("{$column} & ? = 0", [$bitmaskValue]);
    }

    /**
     * Scope a query to include records where the configurable bitmask can transition to a target state.
     * This method only works with columns that use ConfigurableBitmaskCast.
     *
     * @throws InvalidArgumentException
     */
    public function scopeWhereCanTransitionTo(Builder $query, string $column, int|BackedEnum|UnitEnum $targetState): Builder
    {
        $this->validateConfigurableBitmaskColumn($column);
        $castClass = $this->getConfigurableBitmaskCast($column);
        $config = $castClass::config();

        $targetValue = $this->getStateValue($targetState);
        $possibleCurrentStates = $config->getStatesCanTransitionTo($targetValue);

        if (empty($possibleCurrentStates)) {
            // No states can transition to target, return empty result
            return $query->whereRaw('1 = 0');
        }

        // Build OR conditions for all possible current states
        $conditions = [];
        $bindings = [];

        foreach ($possibleCurrentStates as $currentState) {
            $conditions[] = "({$column} & ? = ?)";
            $bindings[] = $currentState;
            $bindings[] = $currentState;
        }

        return $query->whereRaw('(' . implode(' OR ', $conditions) . ')', $bindings);
    }

    /**
     * Scope a query to include records where the bitmask is exactly at a specific state.
     *
     * @throws InvalidArgumentException
     */
    public function scopeWhereAtState(Builder $query, string $column, int|BackedEnum|UnitEnum $state): Builder
    {
        $this->validateColumn($column);
        $stateValue = $this->getStateValue($state);

        return $query->where($column, $stateValue);
    }

    /**
     * Scope a query to include records where the bitmask is in any of the specified states.
     *
     * @param array<int|BackedEnum|UnitEnum> $states
     * @throws InvalidArgumentException
     */
    public function scopeWhereInStates(Builder $query, string $column, array $states): Builder
    {
        $this->validateColumn($column);
        $stateValues = array_map(fn($state) => $this->getStateValue($state), $states);

        return $query->whereIn($column, $stateValues);
    }

    /**
     * Scope a query to include records where all specified states are active.
     *
     * @param array<int|BackedEnum|UnitEnum> $states
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasAllStates(Builder $query, string $column, array $states): Builder
    {
        $this->validateColumn($column);
        $stateValues = array_map(fn($state) => $this->getStateValue($state), $states);
        $mask = array_reduce($stateValues, fn($carry, $value) => $carry | $value, 0);

        return $query->whereRaw("{$column} & ? = ?", [$mask, $mask]);
    }

    /**
     * Scope a query to include records where any of the specified states are active.
     *
     * @param array<int|BackedEnum|UnitEnum> $states
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasAnyStates(Builder $query, string $column, array $states): Builder
    {
        $this->validateColumn($column);
        $stateValues = array_map(fn($state) => $this->getStateValue($state), $states);
        $mask = array_reduce($stateValues, fn($carry, $value) => $carry | $value, 0);

        return $query->whereRaw("{$column} & ? <> 0", [$mask]);
    }

    /**
     * Check if a column uses ConfigurableBitmaskCast and can transition to a target state.
     */
    public function canTransitionTo(string $column, int|BackedEnum|UnitEnum $targetState): bool
    {
        $this->validateConfigurableBitmaskColumn($column);

        $handler = $this->getAttribute($column);
        if (!$handler instanceof ConfigurableBitmaskHandler) {
            return false;
        }

        return $handler->canTransitionTo($targetState);
    }

    /**
     * Get possible transitions for a configurable bitmask column.
     *
     * @return array<int>
     */
    public function getPossibleTransitions(string $column): array
    {
        $this->validateConfigurableBitmaskColumn($column);

        $handler = $this->getAttribute($column);
        if (!$handler instanceof ConfigurableBitmaskHandler) {
            return [];
        }

        return $handler->getPossibleTransitions();
    }

    /**
     * Get active states for a bitmask column.
     *
     * @return array<int>
     */
    public function getActiveStates(string $column): array
    {
        $this->validateColumn($column);

        $handler = $this->getAttribute($column);
        if ($handler instanceof ConfigurableBitmaskHandler) {
            return $handler->getActiveStates();
        }

        // Fallback for regular bitmask columns
        $value = $this->getAttribute($column);
        if (!is_int($value)) {
            return [];
        }

        $activeStates = [];
        for ($i = 0; $i < 32; $i++) {
            $bit = 1 << $i;
            if (($value & $bit) !== 0) {
                $activeStates[] = $bit;
            }
        }

        return $activeStates;
    }

    /**
     * Validate that the provided column is a defined bitmask column.
     *
     * @throws InvalidArgumentException
     */
    protected function validateColumn(string $column): void
    {
        $bitmaskColumns = $this->getBitmaskColumns();
        $casts = $this->getCasts();

        // Check if it's in bitmaskColumns or if it's a configurable bitmask cast
        if (!array_key_exists($column, $bitmaskColumns) && !$this->isConfigurableBitmaskCast($column)) {
            throw new InvalidArgumentException("Column '{$column}' is not a defined bitmask column.");
        }
    }

    /**
     * Validate that the provided column uses ConfigurableBitmaskCast.
     *
     * @throws InvalidArgumentException
     */
    protected function validateConfigurableBitmaskColumn(string $column): void
    {
        if (!$this->isConfigurableBitmaskCast($column)) {
            throw new InvalidArgumentException("Column '{$column}' does not use ConfigurableBitmaskCast.");
        }
    }

    /**
     * Check if a column uses ConfigurableBitmaskCast.
     */
    protected function isConfigurableBitmaskCast(string $column): bool
    {
        $casts = $this->getCasts();
        if (!isset($casts[$column])) {
            return false;
        }

        $castClass = $casts[$column];
        return is_subclass_of($castClass, ConfigurableBitmaskCast::class);
    }

    /**
     * Get the ConfigurableBitmaskCast class for a column.
     *
     * @throws InvalidArgumentException
     */
    protected function getConfigurableBitmaskCast(string $column): string
    {
        $casts = $this->getCasts();
        $castClass = $casts[$column] ?? null;

        if (!$castClass || !is_subclass_of($castClass, ConfigurableBitmaskCast::class)) {
            throw new InvalidArgumentException("Column '{$column}' does not use ConfigurableBitmaskCast.");
        }

        return $castClass;
    }

    /**
     * Retrieve the integer value of a flag, considering its associated Enum if any.
     *
     * @throws InvalidArgumentException
     */
    protected function getFlagValue(string $column, int|BackedEnum $flag): int
    {
        if ($flag instanceof BackedEnum) {
            // If the column has an associated Enum, ensure the flag belongs to it
            $enumClass = $this->getBitmaskColumns()[$column] ?? null;
            if ($enumClass && !($flag instanceof $enumClass)) {
                throw new InvalidArgumentException("Flag does not match the Enum associated with column '{$column}'.");
            }

            return $flag->value;
        }

        if (!is_int($flag)) {
            throw new InvalidArgumentException('Bitmask flag must be an integer or a backed enum.');
        }

        return $flag;
    }

    /**
     * Get the integer value of a state (supports UnitEnum, BackedEnum, and int).
     */
    protected function getStateValue(int|BackedEnum|UnitEnum $state): int
    {
        if ($state instanceof BackedEnum) {
            return $state->value;
        }

        if (is_int($state)) {
            return $state;
        }

        // For UnitEnum, use ordinal position with bit shifting
        if ($state instanceof UnitEnum) {
            $cases = $state::cases();
            $index = array_search($state, $cases, true);
            return 1 << $index;
        }

        return 0;
    }

    /**
     * Validate that the provided flags are integers or backed Enums.
     *
     * @throws InvalidArgumentException
     */
    protected function validateFlags(array $flags): void
    {
        foreach ($flags as $flag) {
            if (!is_int($flag) && !($flag instanceof BackedEnum)) {
                throw new InvalidArgumentException('Bitmask flags must be integers or backed enums.');
            }
        }
    }

    /**
     * Calculate the mask by combining the flag values using OR operation.
     *
     * @param array<int|BackedEnum> $flags
     */
    protected function calculateMask(string $column, array $flags): int
    {
        return array_reduce($flags, function ($carry, $flag) use ($column) {
            return $carry | $this->getFlagValue($column, $flag);
        }, 0);
    }

    /**
     * Get the bitmask columns configuration.
     * This method should be implemented by the model using this trait.
     */
    public function getBitmaskColumns(): array
    {
        return $this->bitmaskColumns ?? [];
    }
}
