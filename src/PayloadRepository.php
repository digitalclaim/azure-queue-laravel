<?php

namespace DigitalClaim\AzureQueue;

use MicrosoftAzure\Storage\Queue\Models\QueueMessage;

/**
 * JobRepository
 */
class PayloadRepository implements PayloadRepositoryInterface
{
    /**
     * get entry to load long message text
     */
    public function get(QueueMessage $message): string
    {
        return $message->getMessageText();
    }

    /**
     * create entry to store long message text and returns the short message text (max 64kb) for the Azure Queue
     */
    public function create(string $payload): string
    {
        return $payload;
    }
}
