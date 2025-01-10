Certainly! Below is the **updated README** for your `laravel-bitmask` package, incorporating the renamed trait `HasBitmask` and ensuring all references and usage examples align with the latest changes. This updated documentation adheres to best practices, provides clear instructions, and showcases the enhanced functionality of your package.

---

# Laravel Bitmask

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/alazzi-az/laravel-bitmask/run-tests.yml?branch=main&label=tests)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/alazzi-az/laravel-bitmask/fix-php-code-style-issues.yml?branch=main&label=code%20style)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)

**Laravel Bitmask** is a powerful wrapper package for integrating the functionality of the [php-bitmask](https://github.com/alazzi-az/php-bitmask) library into Laravel applications. It leverages Laravel's service container and facades to provide an elegant and intuitive interface for reading, validating, and converting bitmasks. The package also simplifies casting bitmasks to and from enum values, enabling developers to efficiently apply bitmasking techniques while harnessing the full power of Laravel’s ecosystem.

## Features

- **Bitmask Reading**: Easily retrieve active bits from a given bitmask.
- **Bitmask Validation**: Ensure that provided bits and masks are valid, including checks for single-bit settings.
- **Bitmask Conversion**: Convert indices to bitmasks and vice versa, along with conversions to binary string representations.
- **Casting for Masks and Enums**: Automatically handle the casting of bitmask values to and from enum types, providing a seamless experience when working with enumerated bitmasks.
- **Integration with Laravel**: Utilizes Laravel's facade system for seamless integration and easy access.
- **Eloquent Query Scopes**: Provides a trait with query scopes for performing bitmask operations directly within your Eloquent models.

## Installation

You can install the package via Composer:

```bash
composer require alazzi-az/laravel-bitmask
```

## Usage

### 1. HasBitmask Trait

The `HasBitmask` trait provides Eloquent query scopes for performing bitmask operations on multiple columns within your models.

#### Overview

The `HasBitmask` trait allows you to:

- **Check if a specific flag is set**.
- **Check if any of a set of flags are set**.
- **Check if all of a set of flags are set**.
- **Check if specific flags are not set**.

#### Integration

To use the `HasBitmask` trait in your Laravel models, follow these steps:

1. **Import and Use the Trait**

   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Model;
   use Alazziaz\LaravelBitmask\Traits\HasBitmask;
   use App\Enums\ArchiveDataFlag;
   use App\Enums\UserPermissionsFlag;

   class Archive extends Model
   {
       use HasBitmask;

       /**
        * Define the bitmask columns and their associated Enums (optional).
        *
        * @var array<string, string|null>
        */
       protected array $bitmaskColumns = [
           'archive_data_flag' => ArchiveDataFlag::class,
           'user_permissions_flag' => UserPermissionsFlag::class,
       ];
   }
   ```

2. **Define Bitmask Columns**

   In your model, define the `$bitmaskColumns` property as shown above. This property is an associative array where keys are the column names storing bitmask values, and values are the corresponding Enum classes. If no Enum is associated, you can set the value to `null`.

#### Usage Examples

Assuming you have an `Archive` model with `archive_data_flag` and `user_permissions_flag` columns, here are some usage examples:

##### a. Querying for a Single Flag

**Objective:** Retrieve all `Archive` records where the `HOTELS` flag is set in the `archive_data_flag` column.

```php
use App\Models\Archive;
use App\Enums\ArchiveDataFlag;

// Using Enum
$archivesWithHotels = Archive::whereHasFlag('archive_data_flag', ArchiveDataFlag::HOTELS)->get();

// Using integer
$archivesWithHotels = Archive::whereHasFlag('archive_data_flag', 64)->get();
```

##### b. Querying for Multiple Flags (Any)

**Objective:** Retrieve all `Archive` records where **any** of the specified flags (`HOTELS` or `CITIES`) are set in the `archive_data_flag` column.

```php
use App\Models\Archive;
use App\Enums\ArchiveDataFlag;

$archives = Archive::whereHasAnyFlags('archive_data_flag', [
    ArchiveDataFlag::HOTELS,
    ArchiveDataFlag::CITIES,
])->get();
```

##### c. Querying for Multiple Flags (All)

**Objective:** Retrieve all `Archive` records where **all** of the specified flags (`HOTELS` and `CITIES`) are set in the `archive_data_flag` column.

```php
use App\Models\Archive;
use App\Enums\ArchiveDataFlag;

$archivesWithBoth = Archive::whereHasAllFlags('archive_data_flag', [
    ArchiveDataFlag::HOTELS,
    ArchiveDataFlag::CITIES,
])->get();
```

##### d. Querying Across Multiple Bitmask Columns

**Objective:** Retrieve all `Archive` records where the `HOTELS` flag is set in `archive_data_flag` **and** the `ADMIN` flag is set in `user_permissions_flag`.

```php
use App\Models\Archive;
use App\Enums\ArchiveDataFlag;
use App\Enums\UserPermissionsFlag;

$archives = Archive::whereHasFlag('archive_data_flag', ArchiveDataFlag::HOTELS)
                   ->whereHasFlag('user_permissions_flag', UserPermissionsFlag::ADMIN)
                   ->get();
```

##### e. Excluding Flags

**Objective:** Retrieve all `Archive` records where the `HOTELS` flag is **not** set in the `archive_data_flag` column.

```php
use App\Models\Archive;
use App\Enums\ArchiveDataFlag;

$archivesWithoutHotels = Archive::whereHasNoFlag('archive_data_flag', ArchiveDataFlag::HOTELS)->get();
```
#### [For full enum example](./Docs/Example-Enums.md)
### 2. EnumBitmaskCast

#### Overview

`EnumBitmaskCast` is a custom attribute casting class that converts a bitmask integer into an enum-based object. This allows you to leverage PHP enums in your models for cleaner and more expressive code.

#### Usage

To use `EnumBitmaskCast` in your model, specify the cast in the `$casts` property:

```php
use Alazziaz\LaravelBitmask\Casts\EnumBitmaskCast;

class YourModel extends Model
{
    protected $casts = [
        'permissions' => EnumBitmaskCast::class . ':App\Enums\YourEnumClass',
    ];
}
```

#### Example

```php
$yourModel = YourModel::find(1);
$permissions = $yourModel->permissions; // Returns an instance of EnumBitmaskHandler
```

### 3. BitmaskCast

#### Overview

`BitmaskCast` is another custom attribute casting class that handles integer bitmask values. It allows you to work with bitmask values more easily in your models.

#### Usage

To use `BitmaskCast` in your model, specify the cast in the `$casts` property:

```php
use Alazziaz\LaravelBitmask\Casts\BitmaskCast;

class YourModel extends Model
{
    protected $casts = [
        'flags' => BitmaskCast::class . ':8', // Optional maxBit
    ];
}
```

#### Example

```php
$yourModel = YourModel::find(1);
$flags = $yourModel->flags; // Returns an instance of BitmaskHandler
```

### 4. BitmaskHandler

The `BitmaskHandler` class provides an interface for managing bitmask operations in a Laravel application. It allows for the manipulation of bitmasks through various methods, including adding, deleting, and checking for specific bits.

```php
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;

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

### 5. EnumBitmaskHandler

The `EnumBitmaskHandler` class provides an interface for managing bitmask operations specific to enumerations in a Laravel application. It allows manipulation of bitmasks using enums, enabling you to add, delete, and check for specific bits represented by these enums.

```php
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;

enum YourEnum: int {
    case FIRST = 1;
    case SECOND = 2;
    case THIRD = 4;
}

// Create an EnumBitmaskHandler with specific bits set
$enumBitmaskHandler = BitmaskFacade::enumBitmaskHandler(YourEnum::class, YourEnum::FIRST, YourEnum::SECOND);

// Returns the current mask value (e.g., 3)
$currentValue = $enumBitmaskHandler->getValue(); 

// Add bits to the current mask
$enumBitmaskHandler->add(YourEnum::THIRD);

// Delete a bit from the current mask
$enumBitmaskHandler->delete(YourEnum::FIRST);

// Check if specific bits are set in the current mask
$hasBits = $enumBitmaskHandler->has(YourEnum::SECOND, YourEnum::THIRD);

// Convert the current mask to an array representation
$arrayRepresentation = $enumBitmaskHandler->toArray(); // e.g., ['first' => false, 'second' => true, 'third' => true]
```

#### Example Usage

Here's an example that illustrates how to use the `EnumBitmaskHandler` class:

```php
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;

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

If you want to customize the keys in the resulting array from the `toArray` method, consider implementing the `Alazziaz\LaravelBitmask\Contracts\MaskableEnum` interface for your enum. You can define a `toMaskKey` method to specify custom keys for each enum value. For example:

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

With this approach, the `toArray` method in `EnumBitmaskHandler` can utilize the `toMaskKey` method to generate a more descriptive and meaningful array representation of the current mask.

---

### 6. Additional Bitmask Class Methods

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

You can run the package's test suite using Composer:

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
