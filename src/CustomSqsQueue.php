<?php

namespace Wired00\CustomQueue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\SqsQueue;
use Wired00\CustomQueue\Jobs\CustomSqsJob;

class CustomSqsQueue extends SqsQueue implements QueueContract
{
    /**
     * Pop the next job off of the queue.
     *
     * @param string $queue
     *
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $jobHandlerFactory = resolve('Wired00\CustomQueue\Factories\JobHandlerFactory');

        $queue = $this->getQueue($queue);
        $response = $this->sqs->receiveMessage(
            ['QueueUrl' => $queue, 'AttributeNames' => ['ApproximateReceiveCount']]
        );

        // Inject the job attribute into the payload. Required for Laravel
        $response['Messages'][0]['job'] = config('customqueue.handlers.custom-sqs');

        if ($response['Messages'] !== null && count($response['Messages']) > 0) {
            return new CustomSqsJob(app(), $this->sqs, $response['Messages'][0], 'custom-sqs', $queue, $jobHandlerFactory);
        }
    }
}
