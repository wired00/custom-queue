<?php

namespace Tests\Jobs\Handlers;

use Illuminate\Queue\Jobs\Job;
use Wired00\CustomQueue\Contracts\CustomQueueJobHandler as HandlerContract;

class ProcessSQS implements HandlerContract
{
    /**
     * Execute the job.
     *
     * @param Job $job
     * @param null $data
     *
     * @return void
     */
    public function handle(Job $job, $data = null)
    {
        var_dump(json_decode($data)->Records[0]->s3->object->key);

        $job->delete();
    }
}
