<?php

namespace DigitalClaim\AzureQueue\Tests;

use DigitalClaim\AzureQueue\ServiceProvider;
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
    }
}
