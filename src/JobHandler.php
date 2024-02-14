<?php

namespace DigitalClaim\AzureQueue;

use Illuminate\Queue\WorkerOptions;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use Squigg\AzureQueueLaravel\AzureJob;

class JobHandler
{
    /**
     *
     */
    public function handle(QueueMessage $message, ?string $queue = null): array
    {
        $job = new AzureJob(
            app('queue')->getContainer(),
            app('queue')->getAzure(),
            $message,
            app('queue')->getConnectionName(),
            app('queue')->getQueue($queue)
        );

        $options           = new WorkerOptions();
        $options->backoff  = config('azure-queue-laravel.worker.backoff', 60 * 5);
        $options->maxTries = config('azure-queue-laravel.worker.maxTries', 3);

        return app('queue.worker')->process(app('queue')->getConnectionName(), $job, $options);
    }
}
