<?php

use Alazziaz\LaravelBitmask\Casts\EnumBitmaskCast;
use Alazziaz\LaravelBitmask\Handlers\EnumBitmaskHandler;
use Workbench\App\Enums\Permissions;
use Workbench\App\Models\DummyModel;

it('stores and retrieves bitmask values', function () {
    $model = DummyModel::query()->create([
        'permissions' => EnumBitmaskHandler::create(Permissions::class, Permissions::READ, Permissions::EXECUTE),
    ]);

    expect($model->permissions)
        ->toBeInstanceOf(EnumBitmaskHandler::class)
        ->and($model->permissions->getValue())->toBe(5);
});

it('throws an exception if an invalid enum is provided', function () {
    expect(fn () => new EnumBitmaskCast(stdClass::class))
        ->toThrow(InvalidArgumentException::class, 'The provided class must be an enum.');
});

it('returns an enum bitmask handler instance on get', function () {
    $cast = new EnumBitmaskCast(Permissions::class);
    $model = new DummyModel;

    $result = $cast->get($model, 'permissions', 3, []);

    expect($result)
        ->toBeInstanceOf(EnumBitmaskHandler::class)
        ->and($result->getValue())->toBe(3);
});

it('returns value from maskable on set', function () {
    $cast = new EnumBitmaskCast(Permissions::class);
    $model = new DummyModel;

    $maskable = EnumBitmaskHandler::createWithMask(Permissions::class, 5);
    $value = $cast->set($model, 'permissions', $maskable, []);

    expect($value)->toBe(5);
});

it('returns integer directly on set if value is int', function () {
    $cast = new EnumBitmaskCast(Permissions::class);
    $model = new DummyModel;

    $value = $cast->set($model, 'permissions', 7, []);

    expect($value)->toBe(7);
});

it('returns null on set for invalid value', function () {
    $cast = new EnumBitmaskCast(Permissions::class);
    $model = new DummyModel;

    $value = $cast->set($model, 'permissions', 'invalid_value', []);

    expect($value)->toBeNull();
});
