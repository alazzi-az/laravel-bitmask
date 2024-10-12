<?php

namespace Alazziaz\LaravelBitmask\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Alazziaz\LaravelBitmask\LaravelBitmaskServiceProvider;

class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Alazziaz\\BitmaskHandler\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelBitmaskServiceProvider::class,
        ];
    }


    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');


        $migration = include __DIR__.'/../workbench/database/migrations/0000_00_00_000000_create_dummy_models_table.php';
        $migration->up();

    }
}
