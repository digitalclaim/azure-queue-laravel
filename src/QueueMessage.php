<?php

namespace DigitalClaim\AzureQueue;

use MicrosoftAzure\Storage\Queue\Models\QueueMessage as BaseQueueMessage;

/**
 * QueueMessage
 */
class QueueMessage extends BaseQueueMessage
{
    /**
     * _query
     *
     * @var mixed
     */
    private $_query;

    /**
     * Gets query field.
     */
    public function getQuery(): string
    {
        return $this->_query;
    }

    /**
     * Sets query field.
     */
    public function setQuery(string $query): void
    {
        $this->_query = $query;
    }
}
