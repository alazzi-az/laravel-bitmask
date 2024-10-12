# laravel package to work with bitmasking

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/alazzi-az/laravel-bitmask/run-tests?label=tests)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/alazzi-az/laravel-bitmask/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)

**Laravel Bitmask** is a powerful package for managing bitmask operations in Laravel applications. It provides an elegant and intuitive interface for reading, validating, converting bitmasks, and casting them to and from enum values, enabling developers to leverage bitmasking techniques efficiently.
## Features

- **Bitmask Reading**: Easily retrieve active bits from a given bitmask.
- **Bitmask Validation**: Ensure that provided bits and masks are valid, including checks for single-bit settings.
- **Bitmask Conversion**: Convert indices to bitmasks and vice versa, along with conversions to binary string representations.
- **Casting for Masks and Enums**: Automatically handle the casting of bitmask values to and from enum types, providing a seamless experience when working with enumerated bitmasks.
- **Integration with Laravel**: Utilizes Laravel's facade system for seamless integration and easy access.

## Installation

You can install the package via composer:

```bash
composer require alazzi-az/laravel-bitmask
```

## Usage

### EnumBitmaskCast

#### Overview

`EnumBitmaskCast` is a custom attribute casting class that converts a bitmask integer into an enum-based object. This allows you to leverage PHP enums in your models for cleaner and more expressive code.

#### Usage

To use `EnumBitmaskCast` in your model, specify the cast in the `$casts` property:

```php
use Alazziaz\LaravelBitmask\Casts\EnumBitmaskCast;

class YourModel extends Model
{
    protected $casts = [
        'permissions' => EnumBitmaskCast::class . ':YourEnumClass'
    ];
}
```


#### Example

```php
$yourModel = YourModel::find(1);
$permissions = $yourModel->permissions; // Returns an instance of EnumBitmaskHandler so we can use all availabilities as you see below
```

---

### BitmaskCast

#### Overview

`BitmaskCast` is another custom attribute casting class that handles integer bitmask values. It allows you to work with bitmask values more easily in your models.

#### Usage

To use `BitmaskCast` in your model, specify the cast in the `$casts` property:

```php
use YourVendor\YourPackage\BitmaskCast;

class YourModel extends Model
{
    protected $casts = [
        'flags' => BitmaskCast::class . ':8' // Optional maxBit
    ];
}
```

#### Example

```php
$yourModel = YourModel::find(1);
$flags = $yourModel->flags; // Returns an instance of BitmaskHandler
```

---

#### Conclusion

You can now easily use `EnumBitmaskCast` and `BitmaskCast` in your Laravel models to handle bitmasking and enums efficiently. This allows for clearer and more maintainable code in your application.

For further customization or features, feel free to explore the source code or contact support.



### BitmaskHandler

The `BitmaskHandler` class provides an interface for managing bitmask operations in a Laravel application. It allows for the manipulation of bitmasks through various methods, including adding, deleting, and checking for specific bits.

```php
use Alazziaz\LaravelBitmask\Handlers\BitmaskHandler;

// Create a BitmaskHandler instance with the combined permissions
$permissions = 1 | 2 | 4;
$bitmask = new BitmaskHandler($permissions);
$maskValue = $bitmask->getValue(); // Returns 7

// Create a BitmaskHandler with an initial mask of 0
$bitmaskHandler = BitmaskHandler::create(0);

// Create a BitmaskHandler with an initial mask and a highest bit
$bitmaskHandlerWithLimit = BitmaskHandler::create(0, 7);

// Returns the current mask (e.g., 0)
$currentValue = $bitmaskHandler->getValue();
 
// Returns the binary string representation
$binaryString = $bitmaskHandler->toString(); 

// Adds bits 1 and 2 to the current mask
$bitmaskHandler->add(1, 2);

// Deletes bit 1 from the current mask
$bitmaskHandler->delete(1);

// Returns true if bits 1 and 2 are set
$hasBits = $bitmaskHandler->has(1, 2); 
```

### EnumBitmaskHandler

The `EnumBitmaskHandler` class provides an interface for managing bitmask operations specific to enumerations in a Laravel application. It allows manipulation of bitmasks using enums, enabling you to add, delete, and check for specific bits represented by these enums.

```php
use Alazziaz\LaravelBitmask\Handlers\EnumBitmaskHandler;

// Create an EnumBitmaskHandler instance with no bits set
$enumBitmaskHandler = EnumBitmaskHandler::none('YourEnum');

// Create an EnumBitmaskHandler with specific bits set
$enumBitmaskHandlerWithBits = EnumBitmaskHandler::create('YourEnum', YourEnum::BIT_ONE, YourEnum::BIT_TWO);

// Returns the current mask value (e.g., 0)
$currentValue = $enumBitmaskHandler->getValue(); 

// Add bits to the current mask
$enumBitmaskHandler->add(YourEnum::BIT_THREE);

// Delete a bit from the current mask
$enumBitmaskHandler->delete(YourEnum::BIT_ONE);

// Check if specific bits are set in the current mask
$hasBits = $enumBitmaskHandler->has(YourEnum::BIT_TWO, YourEnum::BIT_THREE);

// Convert the current mask to an array representation
$arrayRepresentation = $enumBitmaskHandler->toArray(); // e.g., ['bit_one' => false, 'bit_two' => true, 'bit_three' => true]
```

#### Methods Overview

- **`none(string $enum): self`**
    - Creates an instance of `EnumBitmaskHandler` with no bits set.

- **`create(string $enum, UnitEnum ...$bits): self`**
    - Creates a new instance with the specified bits set.

- **`createWithMask(string $enum, int $mask): self`**
    - Creates a new instance with the provided mask value.

- **`without(string $enum, UnitEnum ...$bits): self`**
    - Returns a new instance with the specified bits removed.

- **`delete(UnitEnum ...$bits): self`**
    - Removes specified bits from the current instance.

- **`add(UnitEnum ...$bits): self`**
    - Adds specified bits to the current instance.

- **`getValue(): int`**
    - Returns the current mask value as an integer.

- **`toArray(): array`**
    - Returns an array representation of the current mask, indicating which bits are set.

- **`has(UnitEnum ...$bits): bool`**
    - Checks if the specified bits are set in the current mask.

#### Example Usage

Here's an example that illustrates how to use the `EnumBitmaskHandler` class:

```php
// Assuming you have an enum defined as:
enum YourEnum: int {
    case BIT_ONE = 1;
    case BIT_TWO = 2;
    case BIT_THREE = 4;
}

// Creating an instance with no bits set
$bitmaskHandler = EnumBitmaskHandler::none(YourEnum::class);

// Adding bits
$bitmaskHandler->add(YourEnum::BIT_ONE, YourEnum::BIT_TWO);

// Checking current value
$currentValue = $bitmaskHandler->getValue(); // Returns 3

// Checking if specific bits are set
$hasBitOne = $bitmaskHandler->has(YourEnum::BIT_ONE); // Returns true

// Converting to an array
$arrayRepresentation = $bitmaskHandler->toArray(); // ['bit_one' => true, 'bit_two' => true, 'bit_three' => false]
```

#### Note on `toArray` Method

If you want to customize the keys in the resulting array from the `toArray` method, consider implementing the `Alazziaz\LaravelBitmask\Contracts\MaskableEnum` interface for your enum. You can define a `getKey` method to specify custom keys for each enum value. For example:

```php
public function getKey(): string
{
    return match ($this) {
        self::READ => 'read_permission',
        self::WRITE => 'write_permission',
        self::EXECUTE => 'execute_permission',
    };
}
```

With this approach, the `toArray` method in `EnumBitmaskHandler` can utilize the `getKey` method to generate a more descriptive and meaningful array representation of the current mask.

### Bitmask Facade

The `Bitmask` facade provides a simplified interface for managing bitmask operations in a Laravel application. It allows access to various functionalities such as reading, validating, and converting bitmasks.

#### Methods

##### `reader()`
Get the `BitmaskReader` instance.

##### `validator()`
Get the `BitmaskValidator` instance.

##### `converter()`
Get the `BitmaskConverter` instance.

##### Get an array of active bits from the provided bitmask.
```php
$activeBits = Bitmask::getActiveBits(7); // Returns [0, 1, 2]
```

##### Get the index of the most significant bit set in the provided bitmask.

```php
$index = Bitmask::getMostSignificantBitIndex(5); // Returns 2
```

##### Convert the provided bitmask to a binary string representation.
```php
$binaryString = Bitmask::convertToBinaryString(7); // Returns '111'
```

#####  Convert an index to its corresponding bitmask value.
```php
$mask = Bitmask::indexToBitMask(3); // Returns 8
```

##### Convert a bitmask to its corresponding index.
```php
$index = Bitmask::bitMaskToIndex(8); // Returns 3
```

##### Validate that the provided bit is valid.
```php
Bitmask::validateBit(2); // No exception means valid
```

##### Validate that the provided mask is valid.
```php
Bitmask::validateMask(15); // No exception means valid
```

#####  Check if only one bit is set in the provided mask.
```php
$isSingleBit = Bitmask::isOnlyOneBitSet(8); // Returns true
```

##### Ensure that only a single bit is set in the provided bitmask.
```php
Bitmask::ensureSingleBitIsSet(4); // Validates that only one bit is set
```

### Usage Example

Here's a simple example demonstrating the usage of the `Bitmask` facade:

```php
use Alazziaz\LaravelBitmask\Facades\Bitmask;

// Get active bits from a bitmask
$activeBits = Bitmask::getActiveBits(7); // Returns [0, 1, 2]

// Validate a bit
Bitmask::validateBit(2);

// Convert to binary string
$binaryString = Bitmask::convertToBinaryString(5); // Returns '101'
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Mohammed Azman](https://github.com/56982649+mohammedazman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
