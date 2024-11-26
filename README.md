# This is my package azure-queue-laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/digitalclaim/azure-queue-laravel.svg?style=flat-square)](https://packagist.org/packages/digitalclaim/azure-queue-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/digitalclaim/azure-queue-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/digitalclaim/azure-queue-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/digitalclaim/azure-queue-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/digitalclaim/azure-queue-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/digitalclaim/azure-queue-laravel.svg?style=flat-square)](https://packagist.org/packages/milo/azure-queue-laravel)

Azure Storage Queue for Laravel. This works fundamentally different than the normal Laravel queue worker. We will push items to the storage queue as usual, but instead of constant pulling with something like `queue:liste` we use Azure Storage Queue trigger to call a Azure Function. The Azure Function will then call our Laravel app with the job payload to process it. There is no need to run a `queue:work/listen` command.

This package is inspired by [stackkit/laravel-google-cloud-tasks-queue](https://github.com/stackkit/laravel-google-cloud-tasks-queue) and based on [squigg/azure-queue-laravel](https://github.com/squigg/azure-queue-laravel).

Warning: Currently we do not save failed jobs in any way. However, it's possible to use Laravel queue callbacks to do so.

```php
public function boot(): void
{
    Queue::before(function (JobProcessing $event) {
        // before processing
    });

    Queue::after(function (JobProcessed $event) {
        // after processing
    });

    Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
        // on error (for each retry)
    });

    Queue::failing(function (JobFailed $event) {
        // on fail (after all retries)
    });
}
```

## Installation

You can install the package via composer:

```bash
composer require digitalclaim/azure-queue-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="azure-queue-laravel-config"
```

With the config it is possible to set a custom repository for the payload. This is usefull when one has to work with messages > 64kb (Azure Limit). This is the contents of the published config file:

```php
use DigitalClaim\AzureQueue\PayloadRepository;

return [
    'job' => [
        'payloadRepository' => PayloadRepository::class,
    ],
    'worker' => [
        'backoff' => env('DIGITALCLAIM_AZURE_QUEUE_LARAVEL_BACKOFF', 60 * 5),
        'maxTries' => env('DIGITALCLAIM_AZURE_QUEUE_LARAVEL_MAXTRIES', 3),
    ],
];

```

Some example for a custom payload repository:

```php
'job' => [
    'payloadRepository' => new class extends \DigitalClaim\AzureQueue\PayloadRepository
    {
        /**
         * get entry to load long message text
         */
        public function get(QueueMessage $message): string
        {
            $payload = json_decode($message->getMessageText(), true);

            $entry = ...; // load entry from you db

            return json_encode($entry['payload']);
        }

        /**
         * create entry to store long message text and returns the short message text (max 64kb) for the Azure Queue
         */
        public function create(string $payload): string
        {
            $payload = json_decode($payload, true);

            $id = ...; // save payload to db entry

            return json_encode([
                'id' => $id,
            ]);
        }
    },
],
```

## Usage

1. You already have setup your Azure App Service and Storage Account
2. Add new queue connection in `config/queue.php`

```php
'azure'      => [
    'driver' => 'azurepush', // Leave this as-is
    'protocol' => 'https', // https or http
    'accountname' => env('AZURE_QUEUE_STORAGE_NAME'), // Azure storage account name
    'key' => env('AZURE_QUEUE_KEY'), // Access key for storage account
    'queue' => env('AZURE_QUEUE_NAME'), // Queue container name
    'timeout' => 60, // Seconds before a job is released back to the queue
    'endpoint' => env('AZURE_QUEUE_ENDPOINTSUFFIX'), // Optional endpoint suffix if different from core.windows.net
    'queue_endpoint' => env('AZURE_QUEUE_ENDPOINT'), // Optional endpoint for custom addresses like http://localhost/my_storage_name
],
```

3. Set environment variables

```bash
QUEUE_CONNECTION=azure

AZURE_QUEUE_STORAGE_NAME=YOUR_QUEUE_STORAGE_NAME
AZURE_QUEUE_KEY=YOUR_QUEUE_KEY
AZURE_QUEUE_NAME=YOUR_QUEUE_NAME
#AZURE_QUEUE_ENDPOINTSUFFIX=core.windows.net
#AZURE_QUEUE_ENDPOINT=https
```

4. Trigger Azure Function (nodejs) for new queue items (see https://learn.microsoft.com/en-us/azure/azure-functions/functions-bindings-storage-queue-trigger)

```javascript
const axios = require("axios");

module.exports = async function (context, myQueueItem, more) {
    try {
        const response = await axios.post(
            "https://YOURSITE.azurewebsites.net/handle-queue",
            {
                id: context.bindingData.id,
                message: myQueueItem,
                meta: {
                    dequeueCount: context.bindingData.dequeueCount,
                    expirationTime: context.bindingData.expirationTime,
                    insertionTime: context.bindingData.insertionTime,
                    nextVisibleTime: context.bindingData.nextVisibleTime,
                    popReceipt: context.bindingData.popReceipt,
                },
            }
        );

        context.log(response.data);
    } catch (error) {
        // If the promise rejects, an error will be thrown and caught here
        context.log(error);
    }

    context.done();
};
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [MiloTischler](https://github.com/milo)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
