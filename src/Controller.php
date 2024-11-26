<?php

namespace DigitalClaim\AzureQueue;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;

/**
 * Controller
 */
class Controller
{
    /**
     * AZURE_DATE_TIME_FORMAT
     *
     * @var string Azure date time format
     */
    private const AZURE_DATE_TIME_FORMAT = 'D, d M Y H:i:s T';

    /**
     * jobHandler
     *
     * @var \DigitalClaim\AzureQueue\JobHandler
     */
    protected $jobHandler;

    /**
     * __construct
     *
     * @param  mixed  $jobHandler
     * @return void
     */
    public function __construct(JobHandler $jobHandler)
    {
        $this->jobHandler = $jobHandler;
    }

    /**
     * handle
     *
     * @param  mixed  $request
     * @return void
     */
    public function handle(JobRequest $request)
    {
        $input = $request->validated();

        $message = QueueMessage::createFromListMessages([
            'MessageId' => Arr::get($input, 'id'),
            'DequeueCount' => Arr::get($input, 'meta.dequeueCount'),
            'ExpirationTime' => Carbon::parse(Arr::get($input, 'meta.expirationTime'))->format(self::AZURE_DATE_TIME_FORMAT),
            'InsertionTime' => Carbon::parse(Arr::get($input, 'meta.insertionTime'))->format(self::AZURE_DATE_TIME_FORMAT),
            'TimeNextVisible' => Carbon::parse(Arr::get($input, 'meta.nextVisibleTime'))->format(self::AZURE_DATE_TIME_FORMAT),
            'PopReceipt' => Arr::get($input, 'meta.popReceipt'),
            'MessageText' => json_encode(Arr::get($input, 'message')),
        ]);

        $job = $this->jobHandler->handle($message, Arr::get($input, 'meta.queueName', null));

        return [
            'uuid' => $job->uuid(),
        ];
    }
}
