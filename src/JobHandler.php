<?php

namespace DigitalClaim\AzureQueue;

use Illuminate\Queue\WorkerOptions;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use Squigg\AzureQueueLaravel\AzureJob;

/**
 * JobHandler
 */
class JobHandler
{
    /**
     * handle
     *
     * @param  mixed  $message
     * @param  mixed  $queue
     */
    public function handle(QueueMessage $message, ?string $queue = null): AzureJob
    {
        $messageText = resolve(PayloadRepositoryInterface::class)->get($message);

        $message->setMessageText($messageText);

        $job = new AzureJob(
            app('queue')->getContainer(),
            app('queue')->getAzure(),
            $message,
            app('queue')->getConnectionName(),
            app('queue')->getQueue($queue)
        );

        $options = new WorkerOptions();
        $options->backoff = config('azure-queue-laravel.worker.backoff', 60 * 5);
        $options->maxTries = config('azure-queue-laravel.worker.maxTries', 3);

        app('queue.worker')->process(app('queue')->getConnectionName(), $job, $options);

        return $job;
    }
}
