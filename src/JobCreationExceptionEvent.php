<?php

namespace DigitalClaim\AzureQueue;

use Exception;

class JobCreationExceptionEvent
{
    /**
     * The connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * The payload.
     *
     * @var string
     */
    public $payload;

    /**
     * The exception instance.
     *
     * @var \Throwable
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Throwable  $exception
     * @return void
     */
    public function __construct($connectionName, $payload, $exception)
    {
        $this->payload = $payload;
        $this->exception = $exception;
        $this->connectionName = $connectionName;
    }
}
