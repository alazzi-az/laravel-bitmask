<?php

namespace Alazziaz\LaravelBitmask\Traits;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Trait Bitmaskable
 *
 * Provides Eloquent query scopes for bitmask operations on multiple columns.
 *
 * Usage:
 * 1. Define the $bitmaskColumns property in your model.
 * 2. Use the provided scope methods to perform queries.
 *
 * Example:
 *
 * class Archive extends Model
 * {
 *     use Bitmaskable;
 *
 *     protected array $bitmaskColumns = [
 *         'archive_data_flag' => ArchiveDataFlag::class,
 *         'user_permissions_flag' => UserPermissionsFlag::class,
 *     ];
 * }
 */
trait HasBitmask
{
    /**
     * Scope a query to include records where a specific flag is set on a specified column.
     *
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
     * Validate that the provided column is a defined bitmask column.
     *
     *
     * @throws InvalidArgumentException
     */
    protected function validateColumn(string $column): void
    {
        if (! array_key_exists($column, $this->getBitmaskColumns())) {
            throw new InvalidArgumentException("Column '{$column}' is not a defined bitmask column.");
        }
    }

    /**
     * Retrieve the integer value of a flag, considering its associated Enum if any.
     *
     *
     * @throws InvalidArgumentException
     */
    protected function getFlagValue(string $column, int|BackedEnum $flag): int
    {
        if ($flag instanceof BackedEnum) {
            // If the column has an associated Enum, ensure the flag belongs to it
            $enumClass = $this->getBitmaskColumns()[$column] ?? null;
            if ($enumClass && ! ($flag instanceof $enumClass)) {
                throw new InvalidArgumentException("Flag does not match the Enum associated with column '{$column}'.");
            }

            return $flag->value;
        }

        if (! is_int($flag)) {
            throw new InvalidArgumentException('Bitmask flag must be an integer or a backed enum.');
        }

        return $flag;
    }

    /**
     * Scope a query to include records where any of the specified flags are set on a specified column.
     *
     * @param  array<int|BackedEnum>  $flags
     *
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasAnyFlags(Builder $query, string $column, array $flags): Builder
    {
        $this->validateColumn($column);
        $this->validateFlags($flags);
        $mask = $this->calculateMask($flags);

        return $query->whereRaw("{$column} & ? <> 0", [$mask]);
    }

    /**
     * Validate that the provided flags are integers or backed Enums.
     *
     *
     * @throws InvalidArgumentException
     */
    protected function validateFlags(array $flags): void
    {
        foreach ($flags as $flag) {
            if (! is_int($flag) && ! ($flag instanceof BackedEnum)) {
                throw new InvalidArgumentException('Bitmask flags must be integers or backed enums.');
            }
        }
    }

    /**
     * Calculate the mask by summing the flag values.
     *
     * @param  array<int|BackedEnum>  $flags
     */
    protected function calculateMask(array $flags): int
    {
        return array_reduce($flags, function ($carry, $flag) {
            return $carry + $this->getFlagValue('', $flag);
        }, 0);
    }

    /**
     * Scope a query to include records where all of the specified flags are set on a specified column.
     *
     * @param  array<int|BackedEnum>  $flags
     *
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasAllFlags(Builder $query, string $column, array $flags): Builder
    {
        $this->validateColumn($column);
        $this->validateFlags($flags);
        $mask = $this->calculateMask($flags);

        return $query->whereRaw("{$column} & ? = ?", [$mask, $mask]);
    }

    /**
     * Scope a query to include records where a specific flag is NOT set on a specified column.
     *
     *
     * @throws InvalidArgumentException
     */
    public function scopeWhereHasNoFlag(Builder $query, string $column, int|BackedEnum $flag): Builder
    {
        $this->validateColumn($column);
        $bitmaskValue = $this->getFlagValue($column, $flag);

        return $query->whereRaw("{$column} & ? = 0", [$bitmaskValue]);
    }

    public function getBitmaskColumns(): array
    {
        return $this->bitmaskColumns;
    }
}
