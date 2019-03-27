<?php

namespace Tests\Jobs\Handlers;

use Wired00\CustomQueue\Contracts\CustomQueueJobHandler as HandlerContract;
use Illuminate\Queue\Jobs\Job;

class ProcessSQS implements HandlerContract
{
    /**
     * Execute the job.
     *
     * @param Job $job
     * @param null $data
     * @return void
     */
    public function handle(Job $job, $data = null)
    {
//        info(json_decode($data)->Records[0]->s3->object->key);
        var_dump(json_decode($data)->Records[0]->s3->object->key);

//        $job->delete();
    }
}
