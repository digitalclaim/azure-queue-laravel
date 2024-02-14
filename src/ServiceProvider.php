<?php

namespace DigitalClaim\AzureQueue;

use DigitalClaim\AzureQueue\Connector;
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
     * @param  mixed $package
     * @return void
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
     *
     * @return void
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
