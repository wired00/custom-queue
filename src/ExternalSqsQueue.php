<?php

namespace Wired00\CustomQueue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\SqsQueue;
use Wired00\CustomQueue\Jobs\ExternalSqsJob;

class ExternalSqsQueue extends SqsQueue implements QueueContract
{
    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);
        $response = $this->sqs->receiveMessage(
            array('QueueUrl' => $queue, 'AttributeNames' => array('ApproximateReceiveCount'))
        );
//        $response['Messages'][0]['job'] = 'App\\Jobs\\ProcessSQS@handle';

        // Inject the job attribute into the payload. Required for Laravel
        $response['Messages'][0]['job'] = config('externalqueue.handlers.eis-sqs');

        if ($response['Messages'] !== null && count($response['Messages']) > 0) {
            return new ExternalSqsJob(app(), $this->sqs, $response['Messages'][0], 'externalsqs', $queue);
        }
    }
}
