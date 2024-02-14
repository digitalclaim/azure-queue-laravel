<?php

namespace DigitalClaim\AzureQueue;

use Illuminate\Queue\Connectors\ConnectorInterface;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use Squigg\AzureQueueLaravel\AzureQueue;

class Connector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     */
    public function connect(array $config): AzureQueue
    {
        $connectionString = 'DefaultEndpointsProtocol='.$config['protocol'].';AccountName='.$config['accountname'].';AccountKey='.$config['key'];

        if (isset($config['endpoint']) && $config['endpoint'] !== '') {
            $connectionString .= ';EndpointSuffix='.$config['endpoint'];
        }

        if (isset($config['queue_endpoint']) && $config['queue_endpoint'] !== '') {
            $connectionString .= ';QueueEndpoint='.$config['queue_endpoint'];
        }

        $queueRestProxy = QueueRestProxy::createQueueService($connectionString);

        return new class($queueRestProxy, $config['queue'], $config['timeout']) extends AzureQueue
        {
            /**
             * Push a raw payload onto the queue.
             *
             * @param  string  $payload
             * @param  string  $queue
             */
            public function pushRaw($payload, $queue = null, array $options = []): void
            {
                $this->azure->createMessage($this->getQueue($queue), base64_encode($payload));
            }
        };
    }
}
