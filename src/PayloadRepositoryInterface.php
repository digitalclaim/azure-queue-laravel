<?php

namespace DigitalClaim\AzureQueue;

interface PayloadRepositoryInterface
{
    /**
     * get entry to load long message text
     */
    public function get(QueueMessage $message): string;

    /**
     * create entry to store long message text and returns the short message text (max 64kb) for the Azure Queue
     */
    public function create(string $payload): string;
}
