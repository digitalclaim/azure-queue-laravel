<?php

namespace DigitalClaim\AzureQueue\Tests;

use DigitalClaim\AzureQueue\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Factory::guessFactoryNamesUsing(
        //     fn(string $modelName) => 'DigitalClaim\\AzureQueue\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        // );
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        config()->set('queue.default', 'azurepush');
        config()->set('queue.connections.azurepush', [
            'driver' => env('AZURE_QUEUE_DRIVER', 'azurepush'),
            'protocol' => env('AZURE_QUEUE_PROTOCOL', 'http'),
            'accountname' => env('AZURE_QUEUE_ACCOUNT_NAME', 'devstoreaccount1'),
            'key' => env('AZURE_QUEUE_KEY', 'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw=='),
            'queue' => env('AZURE_QUEUE_NAME', 'package-test-queue'),
            'queue_endpoint' => env('AZURE_QUEUE_ENDPOINT', 'http://127.0.0.1:10001/devstoreaccount1'),
        ]);
    }
}
