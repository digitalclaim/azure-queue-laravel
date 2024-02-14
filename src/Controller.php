<?php

namespace DigitalClaim\AzureQueue;

use Carbon\Carbon;
use DigitalClaim\AzureQueue\JobHandler;
use DigitalClaim\AzureQueue\JobRequest;
use Illuminate\Support\Arr;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;

class Controller
{
    /**
     * @var String Azure date time format
     */
    private const AZURE_DATE_TIME_FORMAT = 'D, d M Y H:i:s T';

    /**
     * @var \DigitalClaim\AzureQueue\JobHandler
     */
    protected $jobHandler;

    /**
     *
     */
    public function __construct(JobHandler $jobHandler)
    {
        $this->jobHandler = $jobHandler;
    }

    /**
     *
     */
    public function handle(JobRequest $request)
    {
        \Log::info('We got some request', [
            'request' => $request->all(),
        ]);

        $input = $request->validated();

        $message = QueueMessage::createFromListMessages([
            'MessageId'       => Arr::get($input, 'id'),
            'DequeueCount'    => Arr::get($input, 'meta.dequeueCount'),
            'ExpirationTime'  => Carbon::parse(Arr::get($input, 'meta.expirationTime'))->format(self::AZURE_DATE_TIME_FORMAT),
            'InsertionTime'   => Carbon::parse(Arr::get($input, 'meta.insertionTime'))->format(self::AZURE_DATE_TIME_FORMAT),
            'TimeNextVisible' => Carbon::parse(Arr::get($input, 'meta.nextVisibleTime'))->format(self::AZURE_DATE_TIME_FORMAT),
            'PopReceipt'      => Arr::get($input, 'meta.popReceipt'),
            'MessageText'     => json_encode(Arr::get($input, 'message')),
        ]);

        return $this->jobHandler->handle($message, Arr::get($input, 'meta.queueName', null));
    }
}
