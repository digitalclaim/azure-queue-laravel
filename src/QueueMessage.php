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
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->_query;
    }

    /**
     * Sets query field.
     *
     * @param string $query
     */
    public function setQuery(string $query): void
    {
        $this->_query = $query;
    }
}
