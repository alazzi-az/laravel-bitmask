<?php

namespace Alazziaz\LaravelBitmask;



use Alazziaz\Bitmask\Bitmask;
use Alazziaz\Bitmask\Util\BitmaskConverter;
use Alazziaz\Bitmask\Util\BitmaskReader;
use Alazziaz\Bitmask\Validators\BitmaskValidator;
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

        $this->app->bind('bitmask', function ($app) {
            return new Bitmask();
        });
    }

}
