<?php

use Alazziaz\LaravelBitmask\Casts\BitmaskCast;
use Alazziaz\LaravelBitmask\Casts\EnumBitmaskCast;
use Alazziaz\Bitmask\Handlers\BitmaskHandler;
use Alazziaz\LaravelBitmask\Facades\BitmaskFacade;
use Workbench\App\Models\DummyModel;

it('stores and retrieves bitmask values', function () {
    $model = DummyModel::query()->create(['flags' => BitmaskFacade::bitmaskHandler( 5)]);

    expect($model->flags)
        ->toBeInstanceOf(BitmaskHandler::class)
        ->and($model->flags->getValue())->toBe(5);
});

it('throws an exception for invalid enum class', function () {
    expect(fn() => new EnumBitmaskCast(stdClass::class))
        ->toThrow(InvalidArgumentException::class);
});

it('throws an exception for non-integer bitmask value on get', function () {
    $cast = new BitmaskCast();

    $dummyModel = new DummyModel();
    expect(fn() => $cast->get($dummyModel, 'flags', 'invalid_value', []))
        ->toThrow(InvalidArgumentException::class, "BitmaskFacade value must be an integer.");
});

it('returns null on get if value is null', function () {
    $cast = new BitmaskCast();

    $dummyModel = new DummyModel();
    $result = $cast->get($dummyModel, 'flags', null, []);
    expect($result)->toBeNull();
});

it('returns BitmaskHandler instance on get with valid integer', function () {
    $cast = new BitmaskCast();
    $dummyModel = new DummyModel();
    $result = $cast->get($dummyModel, 'flags', 5, []);

    expect($result)->toBeInstanceOf(BitmaskHandler::class);
    expect($result->getValue())->toBe(5);
});

it('returns value from Maskable instance on set', function () {
    $cast = new BitmaskCast();
    $dummyModel = new DummyModel();

    $maskable = BitmaskFacade::bitmaskHandler( 5);

    $value = $cast->set($dummyModel, 'flags', $maskable, []);
    expect($value)->toBe(5);
});

it('returns integer directly on set if value is int', function () {
    $cast = new BitmaskCast();
    $dummyModel = new DummyModel();
    $dummyModel = new DummyModel();

    $value = $cast->set($dummyModel, 'flags', 10, []);
    expect($value)->toBe(10);
});

it('returns null on set for invalid value', function () {
    $cast = new BitmaskCast();
    $dummyModel = new DummyModel();

    $value = $cast->set($dummyModel, 'flags', 'invalid_value', []);
    expect($value)->toBeNull();
});
