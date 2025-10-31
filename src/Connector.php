<?php

namespace DigitalClaim\AzureQueue;

use Exception;
use Illuminate\Queue\Connectors\ConnectorInterface;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use Squigg\AzureQueueLaravel\AzureQueue;

/**
 * Connector
 */
class Connector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  mixed  $config
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

        return new class($queueRestProxy, $config['queue'], 0) extends AzureQueue
        {
            /**
             * Push a raw payload onto the queue.
             *
             * @param  string  $payload
             * @param  string  $queue
             */
            public function pushRaw($payload, $queue = null, array $options = []): void
            {
                $payload = resolve(PayloadRepositoryInterface::class)->create($payload);

                try {
                    $this->azure->createMessage($this->getQueue($queue), base64_encode($payload));
                } catch (Exception $exception) {
                    if ($this->container->bound('events')) {
                        $this->container['events']->dispatch(new JobCreationExceptionEvent($this->connectionName, $payload, $exception));
                    }

                    throw $exception;
                }
            }
        };
    }
}
