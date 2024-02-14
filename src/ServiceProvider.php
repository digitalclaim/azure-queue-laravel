<?php

namespace DigitalClaim\AzureQueue;

use DigitalClaim\Connector;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceProvider extends PackageServiceProvider
{
    /**
     *
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
            ->hasRoute('api')
            ->hasConfigFile();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function bootingPackage(): void
    {
        /** @var QueueManager $manager */
        $manager = $this->app['queue'];

        $manager->addConnector('azure', function () {
            return new Connector;
        });
    }
}
