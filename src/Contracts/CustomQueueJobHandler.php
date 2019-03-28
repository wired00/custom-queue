<?php

namespace Wired00\CustomQueue\Contracts;

use Illuminate\Queue\Jobs\Job;

interface CustomQueueJobHandler
{
    /**
     * Triggered by the worker in order to process the job.
     *
     * @param Job  $job  The job
     * @param data $data The data in the message
     *
     * @return void
     */
    public function handle(Job $job, $data = null);
}
