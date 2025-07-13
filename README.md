# Laravel Bitmask State Machine

[![Latest Version on Packagist](https://img.shields.io/packagist/v/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/alazzi-az/laravel-bitmask/run-tests.yml?branch=main&label=tests)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/alazzi-az/laravel-bitmask/fix-php-code-style-issues.yml?branch=main&label=code%20style)](https://github.com/alazzi-az/laravel-bitmask/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/alazzi-az/laravel-bitmask.svg?style=flat-square)](https://packagist.org/packages/alazzi-az/laravel-bitmask)

**Laravel Bitmask State Machine** is a powerful Laravel package that provides configurable bitmask-based state machines with automatic transition validation, Eloquent integration, and comprehensive query capabilities. Perfect for managing complex workflows, permissions, feature flags, and multi-step processes.

## 🚀 Key Features

- **🔄 Configurable State Machines**: Define complex workflows with automatic transition validation
- **🎯 Eloquent Integration**: Seamless Laravel model integration with custom casts
- **📊 Powerful Query Scopes**: Advanced database queries for state-based operations
- **🔀 Flexible Workflows**: Support for linear, parallel, and OR-logic state transitions
- **🛡️ Type Safety**: Full PHP enum support with compile-time validation
- **⚡ High Performance**: Optimized for large-scale applications with minimal overhead

## 📦 Installation

Install the package via Composer:

```bash
composer require alazzi-az/laravel-bitmask
```

## 🎯 Quick Start

### 1. Define Your State Enum

```php
<?php

enum PaymentSteps: int
{
    case ORDER_CREATED = 1 << 0;        // 1
    case PAYMENT_METHOD = 1 << 1;       // 2
    case ADDRESS_VALIDATED = 1 << 2;    // 4
    case PAYMENT_PROCESSED = 1 << 3;    // 8
    case INVENTORY_RESERVED = 1 << 4;   // 16
    case SHIPPING_CALCULATED = 1 << 5;  // 32
    case ORDER_CONFIRMED = 1 << 6;      // 64
    case FULFILLMENT_STARTED = 1 << 7;  // 128
    case SHIPPED = 1 << 8;              // 256
    case DELIVERED = 1 << 9;            // 512
}
```

### 2. Create a Configurable Bitmask Cast

```php
<?php

use Alazziaz\LaravelBitmask\Casts\ConfigurableBitmaskCast;
use Alazziaz\LaravelBitmask\StateMachine\BitmaskStateConfiguration;

class OrderProgressCast extends ConfigurableBitmaskCast
{
    public static function config(): BitmaskStateConfiguration
    {
        return (new BitmaskStateConfiguration(PaymentSteps::class))
            // Linear progression
            ->allowTransition([], PaymentSteps::ORDER_CREATED)
            ->allowTransition([PaymentSteps::ORDER_CREATED], PaymentSteps::PAYMENT_METHOD)
            ->allowTransition([PaymentSteps::PAYMENT_METHOD], PaymentSteps::ADDRESS_VALIDATED)

            // Parallel processes after address validation
            ->allowTransition([PaymentSteps::ADDRESS_VALIDATED], PaymentSteps::PAYMENT_PROCESSED)
            ->allowTransition([PaymentSteps::ADDRESS_VALIDATED], PaymentSteps::INVENTORY_RESERVED)
            ->allowTransition([PaymentSteps::ADDRESS_VALIDATED], PaymentSteps::SHIPPING_CALCULATED)

            // Order confirmation requires all parallel processes
            ->allowTransition([
                PaymentSteps::PAYMENT_PROCESSED,
                PaymentSteps::INVENTORY_RESERVED,
                PaymentSteps::SHIPPING_CALCULATED
            ], PaymentSteps::ORDER_CONFIRMED)

            // Fulfillment chain
            ->allowTransition([PaymentSteps::ORDER_CONFIRMED], PaymentSteps::FULFILLMENT_STARTED)
            ->allowTransition([PaymentSteps::FULFILLMENT_STARTED], PaymentSteps::SHIPPED)
            ->allowTransition([PaymentSteps::SHIPPED], PaymentSteps::DELIVERED)

            ->allowMultipleActive(true)      // Multiple states can be active
            ->requireAllPrerequisites(true); // All prerequisites must be met (AND logic)
    }
}
```

### 3. Integrate with Your Eloquent Model

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Alazziaz\LaravelBitmask\Traits\HasBitmask;

class Order extends Model
{
    use HasBitmask;

    protected $fillable = ['customer_id', 'total_amount', 'progress_state'];

    protected $casts = [
        'progress_state' => OrderProgressCast::class,
    ];

    // Helper methods
    public function canProcessPayment(): bool
    {
        return $this->progress_state?->canTransition(PaymentSteps::PAYMENT_PROCESSED) ?? false;
    }

    public function advanceToStep(PaymentSteps $step): bool
    {
        if ($this->progress_state?->canTransition($step)) {
            $this->progress_state->addState($step);
            return $this->save();
        }
        return false;
    }
}
```

### 4. Use the State Machine

```php
<?php

// Create a new order
$order = Order::create([
    'customer_id' => 1,
    'total_amount' => 99.99,
    'progress_state' => 0
]);

// Progress through the workflow
$order->progress_state->addState(PaymentSteps::ORDER_CREATED);
$order->progress_state->addState(PaymentSteps::PAYMENT_METHOD);
$order->progress_state->addState(PaymentSteps::ADDRESS_VALIDATED);

// Start parallel processes
$order->progress_state->addState(PaymentSteps::PAYMENT_PROCESSED);
$order->progress_state->addState(PaymentSteps::INVENTORY_RESERVED);
$order->progress_state->addState(PaymentSteps::SHIPPING_CALCULATED);

// Confirm order (all prerequisites met)
$order->progress_state->addState(PaymentSteps::ORDER_CONFIRMED);

$order->save();

// Check current state
echo "Order value: " . $order->progress_state->getValue(); // 127
echo "Active states: " . implode(', ', $order->progress_state->getActiveStates());
echo "Can ship? " . ($order->progress_state->canTransition(PaymentSteps::SHIPPED) ? 'Yes' : 'No');
```

## 🔧 Core Components

### ConfigurableBitmaskCast

The heart of the package - a Laravel cast that transforms integer bitmask values into powerful state machine handlers.

#### Features:

- **Automatic Validation**: Prevents invalid state transitions
- **Enum Integration**: Full support for PHP enums
- **Flexible Configuration**: Linear, parallel, and OR-logic workflows
- **Laravel Integration**: Seamless Eloquent model integration

#### Configuration Options:

```php
public static function config(): BitmaskStateConfiguration
{
    return (new BitmaskStateConfiguration(YourEnum::class))
        ->allowTransition($prerequisites, $targetState)
        ->allowMultipleActive(true|false)        // Multiple states simultaneously
        ->requireAllPrerequisites(true|false)    // AND vs OR logic
        ->setEnumClass(YourEnum::class);         // Optional: specify enum class
}
```

### HasBitmask Trait

Provides powerful Eloquent query scopes for database operations on bitmask columns.

#### Available Scopes:

```php
// Traditional bitmask operations
Order::whereHasFlag('permissions', Permission::ADMIN)->get();
Order::whereHasAnyFlags('permissions', [Permission::READ, Permission::WRITE])->get();
Order::whereHasAllFlags('permissions', [Permission::READ, Permission::WRITE])->get();
Order::whereHasNoFlag('permissions', Permission::ADMIN)->get();

// State machine operations
Order::whereAtState('progress_state', PaymentSteps::ORDER_CONFIRMED)->get();
Order::whereInStates('progress_state', [PaymentSteps::SHIPPED, PaymentSteps::DELIVERED])->get();
Order::whereCanTransitionTo('progress_state', PaymentSteps::SHIPPED)->get();
Order::whereHasAllStates('progress_state', [PaymentSteps::PAYMENT_PROCESSED, PaymentSteps::INVENTORY_RESERVED])->get();
Order::whereHasAnyStates('progress_state', [PaymentSteps::SHIPPED, PaymentSteps::DELIVERED])->get();
```

#### Model Integration:

```php
class Order extends Model
{
    use HasBitmask;

    protected $bitmaskColumns = [
        'permissions' => Permission::class,  // Traditional bitmask
    ];

    protected $casts = [
        'progress_state' => OrderProgressCast::class,  // State machine
    ];
}
```

## 🎨 Usage Patterns

### Linear Workflow

Perfect for step-by-step processes where each step must be completed in order:

```php
class LinearWorkflowCast extends ConfigurableBitmaskCast
{
    public static function config(): BitmaskStateConfiguration
    {
        return (new BitmaskStateConfiguration(Steps::class))
            ->allowTransition([], Steps::STEP_1)
            ->allowTransition([Steps::STEP_1], Steps::STEP_2)
            ->allowTransition([Steps::STEP_2], Steps::STEP_3)
            ->allowTransition([Steps::STEP_3], Steps::STEP_4)
            ->allowMultipleActive(false)  // Only one step at a time
            ->requireAllPrerequisites(true);
    }
}
```

### Parallel Processing

For workflows where multiple processes can run simultaneously:

```php
class ParallelWorkflowCast extends ConfigurableBitmaskCast
{
    public static function config(): BitmaskStateConfiguration
    {
        return (new BitmaskStateConfiguration(Tasks::class))
            ->allowTransition([], Tasks::INIT)
            ->allowTransition([Tasks::INIT], Tasks::PROCESS_A)
            ->allowTransition([Tasks::INIT], Tasks::PROCESS_B)
            ->allowTransition([Tasks::INIT], Tasks::PROCESS_C)
            ->allowTransition([Tasks::PROCESS_A, Tasks::PROCESS_B, Tasks::PROCESS_C], Tasks::COMPLETE)
            ->allowMultipleActive(true)   // Multiple processes simultaneously
            ->requireAllPrerequisites(true);  // All must complete before final step
    }
}
```

### OR Logic Prerequisites

For flexible workflows where any one of several conditions can trigger the next step:

```php
class FlexibleWorkflowCast extends ConfigurableBitmaskCast
{
    public static function config(): BitmaskStateConfiguration
    {
        return (new BitmaskStateConfiguration(Options::class))
            ->allowTransition([], Options::START)
            ->allowTransition([Options::START], Options::PATH_A)
            ->allowTransition([Options::START], Options::PATH_B)
            ->allowTransition([Options::PATH_A, Options::PATH_B], Options::FINISH)  // Either path works
            ->allowMultipleActive(true)
            ->requireAllPrerequisites(false);  // OR logic - any prerequisite is sufficient
    }
}
```

## 🔍 Advanced Features

### Dynamic State Checking

```php
$order = Order::find(1);
$progress = $order->progress_state;

// Check what's currently active
$activeStates = $progress->getActiveStates();
$possibleTransitions = $progress->getPossibleTransitions();

// Validate specific transitions
if ($progress->canTransition(PaymentSteps::SHIPPED)) {
    $progress->addState(PaymentSteps::SHIPPED);
}

// Remove states (always allowed)
$progress->removeState(PaymentSteps::INVENTORY_RESERVED);

// Get comprehensive state information
$stateInfo = $progress->toArray();
/*
[
    'value' => 127,
    'active_states' => [1, 2, 4, 8, 16, 32, 64],
    'possible_transitions' => [128],
    'binary' => '01111111'
]
*/
```

### Complex Database Queries

```php
// Find orders that can be shipped
$shippableOrders = Order::whereCanTransitionTo('progress_state', PaymentSteps::SHIPPED)->get();

// Find orders with payment processed but not yet confirmed
$pendingConfirmation = Order::whereHasAllStates('progress_state', [
    PaymentSteps::PAYMENT_PROCESSED,
    PaymentSteps::INVENTORY_RESERVED,
    PaymentSteps::SHIPPING_CALCULATED
])->whereAtState('progress_state', PaymentSteps::ORDER_CONFIRMED)->get();

// Find orders in any fulfillment stage
$inFulfillment = Order::whereHasAnyStates('progress_state', [
    PaymentSteps::FULFILLMENT_STARTED,
    PaymentSteps::SHIPPED,
    PaymentSteps::DELIVERED
])->get();
```

### Error Handling and Validation

```php
try {
    $order->progress_state->addState(PaymentSteps::DELIVERED);
} catch (InvalidArgumentException $e) {
    echo "Invalid transition: " . $e->getMessage();
    // "Transition from states [1, 2, 4] to target [512] is not allowed by the state machine configuration."
}

// Safe transition checking
if ($order->progress_state->canTransition(PaymentSteps::SHIPPED)) {
    $order->progress_state->addState(PaymentSteps::SHIPPED);
    $order->save();
} else {
    echo "Cannot ship order yet. Current state: " . $order->progress_state->getValue();
}
```

## 🧪 Testing

The package includes comprehensive tests covering:

- **74 test cases** with **6,286 assertions**
- State machine configuration and validation
- Eloquent model integration
- Database query scopes
- Performance under load
- Error handling and edge cases

Run the test suite:

```bash
composer test
```

## 📚 Migration from Legacy Features

If you're using the older `BitmaskCast`, `EnumBitmaskCast`, or basic facade methods, consider migrating to the new state machine approach:

### Before (Legacy):

```php
protected $casts = [
    'permissions' => EnumBitmaskCast::class . ':' . Permission::class,
];
```

### After (Recommended):

```php
class PermissionCast extends ConfigurableBitmaskCast
{
    public static function config(): BitmaskStateConfiguration
    {
        return (new BitmaskStateConfiguration(Permission::class))
            ->allowTransition([], Permission::READ)
            ->allowTransition([Permission::READ], Permission::WRITE)
            ->allowTransition([Permission::WRITE], Permission::ADMIN)
            ->allowMultipleActive(true);
    }
}

protected $casts = [
    'permissions' => PermissionCast::class,
];
```

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on recent changes.

## 🔒 Security

If you discover any security-related issues, please email the maintainers directly instead of using the issue tracker.

## 👥 Credits

- [Mohammed Azman](https://github.com/mohammedazman) - Original author
- [All Contributors](../../contributors) - Thank you!

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

---

**Laravel Bitmask State Machine** - Build robust, validated workflows with the power of bitmasks and the elegance of Laravel. 🚀
