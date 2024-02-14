<?php

namespace DigitalClaim\AzureQueue;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * ServiceProvider
 */
class ServiceProvider extends PackageServiceProvider
{
    /**
     * configurePackage
     *
     * @param  mixed  $package
     */
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('azure-queue-laravel')
            ->hasRoute('web')
            ->hasConfigFile();
    }

    /**
     * bootingPackage
     */
    public function bootingPackage(): void
    {
        /** @var QueueManager $manager */
        $manager = $this->app['queue'];

        $manager->addConnector('azurepush', function () {
            return new Connector;
        });
    }
}
