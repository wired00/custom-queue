<?php

namespace Wired00\CustomQueue\Jobs;

use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\SqsJob;

class CustomSqsJob extends SqsJob implements JobContract
{
    public function __construct($container, $sqs, array $job, $connectionName, $queue)
    {
        parent::__construct($container, $sqs, $this->addJobHandlerToBody($job), $connectionName, $queue);
    }

    private function addJobHandlerToBody(array $job)
    {
        $newBody = [
            "type" => "job",
            "job" => "custom-sqs",
            "data" => $job['Body']
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
     * Extract the payload data from the queue message
     * @return Array The payload data
     */
    protected function getJobData()
    {
        $rawdata = $this->decodePayload();

        return $rawdata['data'];
    }

    /**
     * Spawns a new handler for the specific job
     * @return Wired00\CustomQueue\Contracts\CustomQueueJobHandler The handler for this this job
     */
    protected function resolveHandler()
    {
        $job = $this->getJobName();

        //Get the handler class name
        $classname = config('customqueue.handlers.' . $job, '');

        if (!class_exists($classname) ||
            !in_array('Wired00\CustomQueue\Contracts\CustomQueueJobHandler', class_implements($classname))
        ) {
            throw new \UnexpectedValueException('The handler class for ' . $job . ' was not found');
        }

        return new $classname;
    }

    /**
     * Get the job name
     * @return string The job name
     */
    protected function getJobName()
    {
        $rawdata = $this->decodePayload();

        return $rawdata['job'];
    }

    /**
     * Decode the payload data in the message
     * @return Array The decoded data
     */
    protected function decodePayload()
    {
        return json_decode($this->getRawBody(), true);
    }
}
