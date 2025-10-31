<?php

namespace DigitalClaim\AzureQueue;

use MicrosoftAzure\Storage\Queue\Models\QueueMessage as BaseQueueMessage;

/**
 * QueueMessage
 */
class QueueMessage extends BaseQueueMessage
{
    /**
     * Creates QueueMessage object from parsed XML response of
     * ListMessages.
     *
     * @param array $parsedResponse XML response parsed into array.
     *
     * @internal
     *
     * @return QueueMessage
     */
    public static function createFromListMessages(array $parsedResponse)
    {
        $timeNextVisible = $parsedResponse['TimeNextVisible'];

        $msg  = self::createFromPeekMessages($parsedResponse);
        $date = Utilities::rfc1123ToDateTime($timeNextVisible);
        $msg->setTimeNextVisible($date);
        $msg->setPopReceipt($parsedResponse['PopReceipt']);

        return $msg;
    }

    /**
     * Creates QueueMessage object from parsed XML response of
     * PeekMessages.
     *
     * @param array $parsedResponse XML response parsed into array.
     *
     * @internal
     *
     * @return QueueMessage
     */
    public static function createFromPeekMessages(array $parsedResponse)
    {
        $msg            = new QueueMessage();
        $expirationDate = $parsedResponse['ExpirationTime'];
        $insertionDate  = $parsedResponse['InsertionTime'];

        $msg->setDequeueCount(intval($parsedResponse['DequeueCount']));

        $date = Utilities::rfc1123ToDateTime($expirationDate);
        $msg->setExpirationDate($date);

        $date = Utilities::rfc1123ToDateTime($insertionDate);
        $msg->setInsertionDate($date);

        $msg->setMessageId($parsedResponse['MessageId']);
        $msg->setMessageText($parsedResponse['MessageText']);

        return $msg;
    }

    /**
     * Creates QueueMessage object from parsed XML response of
     * createMessage.
     *
     * @param array $parsedResponse XML response parsed into array.
     *
     * @internal
     *
     * @return QueueMessage
     */
    public static function createFromCreateMessage(array $parsedResponse)
    {
        $msg = new QueueMessage();

        $expirationDate  = $parsedResponse['ExpirationTime'];
        $insertionDate   = $parsedResponse['InsertionTime'];
        $timeNextVisible = $parsedResponse['TimeNextVisible'];

        $date = Utilities::rfc1123ToDateTime($expirationDate);
        $msg->setExpirationDate($date);

        $date = Utilities::rfc1123ToDateTime($insertionDate);
        $msg->setInsertionDate($date);

        $date = Utilities::rfc1123ToDateTime($timeNextVisible);
        $msg->setTimeNextVisible($date);

        $msg->setMessageId($parsedResponse['MessageId']);
        $msg->setPopReceipt($parsedResponse['PopReceipt']);

        return $msg;
    }

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
