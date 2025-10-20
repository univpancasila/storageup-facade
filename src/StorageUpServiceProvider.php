<?php

namespace Univpancasila\StorageUp;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * @author @abdansyakuro.id
 */
class StorageUpServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('storageup')
            ->hasConfigFile()
            ->hasMigration('create_storage_files_table');
    }

    public function packageRegistered()
    {
        $this->app->singleton('storageup', function ($app) {
            return new StorageUpService;
        });

        $this->app->singleton(StorageUp::class, function ($app) {
            return $app->make('storageup');
        });
    }
}
