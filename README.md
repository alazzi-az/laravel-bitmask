# laravel package to work with bitmasking

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/alazzi-az/laravel-bitmask/run-tests?label=tests)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/alazzi-az/laravel-bitmask/fix-php-code-style-issues.yml?branch=main&label=code%20style)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)

**Laravel Bitmask** is a powerful wrapper package for integrating the functionality of the [php-bitmask](https://github.com/alazzi-az/php-bitmask) library into Laravel applications. It leverages Laravel's service container and facades to provide an elegant and intuitive interface for reading, validating, and converting bitmasks. The package also simplifies casting bitmasks to and from enum values, enabling developers to efficiently apply bitmasking techniques while harnessing the full power of Laravel’s ecosystem.## Features

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

## Usage:

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
        'permissions' => EnumBitmaskCast::class . YourEnumClass::class
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
use \Alazziaz\LaravelBitmask\Facades\BitmaskFacade;

// Create a BitmaskHandler instance with the combined permissions
$permissions = 1 | 2 | 4;
$bitmask = BitmaskFacade::bitmaskHandler($permissions);
$maskValue = $bitmask->getValue(); // Returns 7

// Create a BitmaskHandler with an initial mask of 0
$bitmaskHandler = BitmaskFacade::bitmaskHandler(0);

// Create a BitmaskHandler with an initial mask and a highest bit
$bitmaskHandlerWithLimit = BitmaskFacade::bitmaskHandler(0, 7);

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

The `EnumBitmaskHandler` class provides an interface for managing bitmask operations specific to enumerations in a
Laravel application. It allows manipulation of bitmasks using enums, enabling you to add, delete, and check for specific
bits represented by these enums.

```php
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;

enum YourEnum: int {
    case FIRST = 1;
    case SECOND = 2;
    case THIRD = 4;
}
// Create an EnumBitmaskHandler with specific bits set
$enumBitmaskHandler = BitmaskFacade::enumBitmaskHandler(YourEnum::class, YourEnum::BIT_ONE, YourEnum::BIT_TWO);

       
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

- **`remove(UnitEnum ...$bits): self`**
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
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;
// Assuming you have an enum defined as:
enum YourEnum: int {
    case BIT_ONE = 1;
    case BIT_TWO = 2;
    case BIT_THREE = 4;
}

// Creating an instance with no bits set
$bitmaskHandler = BitmaskFacade::enumBitmaskHandlerFactory()
->none(YourEnum::class);

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

If you want to customize the keys in the resulting array from the `toArray` method, consider implementing
the `Alazziaz\Bitmask\Contracts\MaskableEnum` interface for your enum. You can define a `toMaskKey` method to specify
custom keys for each enum value. For example:

```php
public function toMaskKey(): string
{
    return match ($this) {
        self::READ => 'read_permission',
        self::WRITE => 'write_permission',
        self::EXECUTE => 'execute_permission',
    };
}
```

With this approach, the `toArray` method in `EnumBitmaskHandler` can utilize the `toMaskKey` method to generate a more
descriptive and meaningful array representation of the current mask.


---

## Here Extra Bitmask Class Methods

---

#### **1. Exposing BitmaskConverter Methods**

- **`indexToBitMask(int $index): int`**  
  Converts an index to its corresponding bitmask.  
  **Example:**
  ```php
  BitmaskFacade::indexToBitMask(3); // Output: 8
  ```

- **`bitMaskToIndex(int $mask): int`**  
  Converts a bitmask to its index.  
  **Example:**
  ```php
  BitmaskFacade::bitMaskToIndex(8); // Output: 3
  ```

- **`getEnumMaxBitValue(string $enum): int`**  
  Retrieves the maximum bit value for the given enum.

- **`bitMaskToArray(int $mask): array`**  
  Converts a bitmask into an array of active bit values.  
  **Example:**
  ```php
  BitmaskFacade::bitMaskToArray(10); // Output: [2, 8]
  ```

- **`arrayToBitMask(array $bits): int`**  
  Converts an array of bit values to a bitmask.  
  **Example:**
  ```php
  BitmaskFacade::arrayToBitMask([2, 8]); // Output: 10
  ```

---

#### **2. Exposing BitmaskReader Methods**

- **`getActiveBits(int $bitmask): array`**  
  Retrieves the active bit values from a bitmask.  
  **Example:**
  ```php
  BitmaskFacade::getActiveBits(10); // Output: [2, 8]
  ```

- **`getActiveIndexes(int $bitmask): array`**  
  Retrieves the active bit indexes from a bitmask.  
  **Example:**
  ```php
  BitmaskFacade::getActiveIndexes(10); // Output: [1, 3]
  ```

- **`countActiveBits(int $bitmask): int`**  
  Counts the number of active bits in a bitmask.  
  **Example:**
  ```php
  BitmaskFacade::countActiveBits(10); // Output: 2
  ```

- **`getMostSignificantBitIndex(int $bitmask): int`**  
  Returns the index of the most significant active bit.  
  **Example:**
  ```php
  BitmaskFacade::getMostSignificantBitIndex(10); // Output: 3
  ```

- **`getLeastSignificantBitIndex(int $bitmask): int`**  
  Returns the index of the least significant active bit.  
  **Example:**
  ```php
  BitmaskFacade::getLeastSignificantBitIndex(10); // Output: 1
  ```

- **`convertToBinaryString(int $bitmask): string`**  
  Converts a bitmask to its binary string representation.  
  **Example:**
  ```php
  BitmaskFacade::convertToBinaryString(10); // Output: '1010'
  ```

---

#### **3. Exposing BitmaskValidator Methods**

- **`validateBit(int $bit): void`**  
  Validates a single bit to ensure it's valid.  
  **Example:**
  ```php
  BitmaskFacade::validateBit(2); // No exception
  ```

- **`validateBits(array $bits): void`**  
  Validates an array of bits to ensure all are valid.  
  **Example:**
  ```php
  BitmaskFacade::validateBits([2, 8]); // No exception
  ```

---
## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [Mohammed Azman](https://github.com/mohammedazman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
