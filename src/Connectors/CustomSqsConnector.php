<?php

namespace Wired00\CustomQueue\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;
use Wired00\CustomQueue\CustomSqsQueue;

class CustomSqsConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        $sqs = SqsClient::factory($config);

        return new CustomSqsQueue($sqs, $config['queue']);
    }

    /**
     * Get the default configuration for SQS.
     *
     * @param array $config
     *
     * @return array
     */
    protected function getDefaultConfiguration(array $config)
    {
        return array_merge(
            [
                'version' => 'latest',
                'http'    => [
                    'timeout'         => 60,
                    'connect_timeout' => 60,
                ],
            ],
            $config
        );
    }
}
