<?php

namespace DigitalClaim\AzureQueue;

use Illuminate\Contracts\Foundation\Application;
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
        // php artisan vendor:publish --tag=azure-queue-laravel
        $this->publishes([
            __DIR__ . '/../config/azure-queue-laravel.php' => config_path('azure-queue-laravel.php'),
        ], 'azure-queue-laravel');

        $repository = config('azure-queue-laravel.job.payloadRepository', JobRepository::class);

        $this->app->singleton(PayloadRepositoryInterface::class, function (Application $app) use ($repository) {
            return new $repository();
        });

        /** @var QueueManager $manager */
        $manager = $this->app['queue'];

        $manager->addConnector('azurepush', function () {
            return new Connector;
        });
    }
}
