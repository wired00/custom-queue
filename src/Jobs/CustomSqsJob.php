<?php

namespace Wired00\CustomQueue\Jobs;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\SqsJob;
use Wired00\CustomQueue\Contracts\CustomQueueJobHandler;
use Wired00\CustomQueue\Factories\JobHandlerFactory;

class CustomSqsJob extends SqsJob implements JobContract
{
    /** @var JobHandlerFactory */
    protected $jobHandlerFactory;

    /**
     * CustomSqsJob constructor.
     *
     * @param $container
     * @param \Aws\Sqs\SqsClient $sqs
     * @param array $job
     * @param $connectionName
     * @param $queue
     * @param $jobHandlerFactory
     */
    public function __construct($container, $sqs, array $job, $connectionName, $queue, $jobHandlerFactory)
    {
        $this->jobHandlerFactory = $jobHandlerFactory;
        parent::__construct($container, $sqs, $this->addJobHandlerToBody($job), $connectionName, $queue);
    }

    private function addJobHandlerToBody(array $job)
    {
        $newBody = [
            'type' => 'job',
            'job'  => 'custom-sqs',
            'data' => $job['Body'],
        ];

        $job['Body'] = json_encode($newBody);

        return $job;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $handler = $this->resolveHandler();
        $data = $this->getJobData();
        $handler->handle($this, $data);
    }

    /**
     * Extract the payload data from the queue message.
     *
     * @return array The payload data
     */
    protected function getJobData()
    {
        return $this->decodePayload()['data'];
    }

    /**
     * Spawns a new handler for the specific job.
     *
     * @return CustomQueueJobHandler The handler for this this job
     */
    protected function resolveHandler()
    {
        return $this->jobHandlerFactory->create($this->getJobIdentifier());
    }

    /**
     * Get the job name.
     *
     * @return string The job name
     */
    protected function getJobIdentifier()
    {
        return $this->decodePayload()['job'];
    }

    /**
     * Decode the payload data in the message.
     *
     * @return array The decoded data
     */
    protected function decodePayload()
    {
        return json_decode($this->getRawBody(), true);
    }
}
