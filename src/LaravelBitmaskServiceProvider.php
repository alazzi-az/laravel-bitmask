<?php

namespace Alazziaz\LaravelBitmask;


use Alazziaz\LaravelBitmask\Util\BitmaskConverter;
use Alazziaz\LaravelBitmask\Util\BitmaskReader;
use Alazziaz\LaravelBitmask\Validators\BitmaskValidator;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelBitmaskServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {

        $package->name('laravel-bitmask');
    }

    public function  registeringPackage(): void
    {
        $this->app->singleton('bitmask.reader', function () {
            return new BitmaskReader();
        });

        $this->app->singleton('bitmask.validator', function () {
            return new BitmaskValidator();
        });

        $this->app->singleton('bitmask.converter', function () {
            return new BitmaskConverter();
        });

        $this->app->singleton('bitmask', function ($app) {
            return new LaravelBitmask(
                $app->make('bitmask.reader'),
                $app->make('bitmask.validator'),
                $app->make('bitmask.converter')
            );
        });
    }

}
