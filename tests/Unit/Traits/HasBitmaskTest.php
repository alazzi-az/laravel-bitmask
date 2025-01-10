<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Enums\ArchiveDataFlag;
use Workbench\App\Models\DummyModel;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure the database is migrated
    // You can run migrations here if not already handled
    // $this->artisan('migrate');
});

it('can query models with a specific flag using whereHasFlag', function () {

    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value]);
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::CITIES->value]); // 2
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::CITIES->value]); // 34

    $archivesWithLOCATIONS = DummyModel::whereHasFlag('archive_data_flag', ArchiveDataFlag::LOCATIONS)->get();

    expect($archivesWithLOCATIONS->count())->toBe(2);
    $archivesWithLOCATIONS->each(function ($model) {
        expect($model->archive_data_flag->getValue() & ArchiveDataFlag::LOCATIONS->value)->toBe(ArchiveDataFlag::LOCATIONS->value);
    });
});

it('can query models with any of the specified flags using whereHasAnyFlags', function () {

    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value]); // 32
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::CITIES->value]); // 2
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::FACILITIES->value]); // 4
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::CITIES->value]); // 34

    $archives = DummyModel::whereHasAnyFlags('archive_data_flag', [
        ArchiveDataFlag::LOCATIONS,
        ArchiveDataFlag::FACILITIES,
    ])->get();

    expect($archives->count())->toBe(3);
    $archives->each(function ($model) {
        expect($model->archive_data_flag->getValue() & (ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::FACILITIES->value))->not->toBe(0);
    });
});

it('can query models with all of the specified flags using whereHasAllFlags', function () {

    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::CITIES->value]); // 66
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::FACILITIES->value]); // 68
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::CITIES->value | ArchiveDataFlag::FACILITIES->value]); // 70
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::CITIES->value]); // 2

    $archivesWithBoth = DummyModel::whereHasAllFlags('archive_data_flag', [
        ArchiveDataFlag::LOCATIONS,
        ArchiveDataFlag::CITIES,
    ])->get();

    expect($archivesWithBoth->count())->toBe(2);
    $archivesWithBoth->each(function ($model) {
        expect($model->archive_data_flag->getValue() & ArchiveDataFlag::LOCATIONS->value)->toBe(ArchiveDataFlag::LOCATIONS->value);
        expect($model->archive_data_flag->getValue() & ArchiveDataFlag::CITIES->value)->toBe(ArchiveDataFlag::CITIES->value);
    });
});

it('can query models where a specific flag is not set using whereHasNoFlag', function () {

    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value]); // 32
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::CITIES->value]); // 2
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::CITIES->value]); // 34
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::FACILITIES->value]); // 4

    $archivesWithoutLOCATIONS = DummyModel::whereHasNoFlag('archive_data_flag', ArchiveDataFlag::LOCATIONS)->get();

    expect($archivesWithoutLOCATIONS->count())->toBe(2);
    $archivesWithoutLOCATIONS->each(function ($model) {
        expect($model->archive_data_flag->getValue() & ArchiveDataFlag::LOCATIONS->value)->toBe(0);
    });
});

it('can perform complex queries across multiple bitmask columns', function () {

    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value]); // 64
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::CITIES->value]); // 2
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::CITIES->value]); // 66
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::FACILITIES->value]); // 4
    DummyModel::factory()->create(['archive_data_flag' => ArchiveDataFlag::LOCATIONS->value | ArchiveDataFlag::FACILITIES->value]); // 68

    $archives = DummyModel::whereHasFlag('archive_data_flag', ArchiveDataFlag::LOCATIONS)
        ->whereHasFlag('archive_data_flag', ArchiveDataFlag::CITIES)
        ->get();

    expect($archives->count())->toBe(1);
    $archives->each(function ($model) {
        expect($model->archive_data_flag->getValue() & ArchiveDataFlag::LOCATIONS->value)->toBe(ArchiveDataFlag::LOCATIONS->value);
        expect($model->archive_data_flag->getValue() & ArchiveDataFlag::CITIES->value)->toBe(ArchiveDataFlag::CITIES->value);
    });
});

it('throws an exception when querying an undefined bitmask column', function () {

    expect(fn () => DummyModel::whereHasFlag('undefined_column', ArchiveDataFlag::LOCATIONS))
        ->toThrow(InvalidArgumentException::class, "Column 'undefined_column' is not a defined bitmask column.");
});
