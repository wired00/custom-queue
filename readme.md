# Laravel Custom Queues

Laravel queues work well internally when items are both pushed (`SomeJob::dispatch()`) and then fetched via Laravel queue workers. 

But if jobs are pushed to a queue from some other external service with a custom (non Laravel) payload. For example, AWS S3 bucket > SNS > SQS?

This package aims to solve that issue. It will fetch a job,  modify the payload to a format which Laravel and then process via a specified job handler.

## Installation

todo

## Usage

### Configuration

#### queue.php
Setup a custom external sqs connection

```
        'externalsqs' => [
            'driver' => 'externalsqs',
            'key' => env('AWS_ACCESS_KEY_ID', 'your-public-key'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', 'your-secret-key'),
            'queue' => env('SQS_QUEUE', 'your-queue-url'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],
```

Set all these values from your `.env`. I.e: 

```
AWS_ACCESS_KEY_ID=ASIAWMC25A2L7MDO6NGA
AWS_SECRET_ACCESS_KEY=3qZLVRShxQvx2xbTSKD5bllObtwHNH3O/9NqvFNc
SQS_QUEUE=https://sqs.ap-southeast-2.amazonaws.com/123/your-sqs-queue-name
AWS_DEFAULT_REGION=ap-southeast-2
QUEUE_CONNECTION=externalsqs
```

#### customqueue.php
customqueue.php config file simply contains a map between handler class path and an identifier. 

The example contains identifier `custom-sqs` and class path `App\Jobs\ProcessSQS::class`. This will mean that the job payload when fetched from a queue will be appended with a key-value of `job: App\Jobs\ProcessSQS::class` whenever a connection type of `externalsqs` is processed.

Currently CustomQueue only supports custom SQS fetching but in future it might support RabbitMQ and Redis. In those cases customqueue.php would include indenfiers such as `custom-redis` and `custom-rabbitmq`.

### Handler files

Handler files are those references via their class path within `customqueue.php`. They must implement `Wired00\CustomQueue\Contracts\CustomQueueJobHandler`.

A common namespace for these are `App\Jobs`.

For example:
```
<?php

namespace App\Jobs;

use Wired00\CustomQueue\Contracts\CustomQueueJobHandler as HandlerContract;
use Illuminate\Queue\Jobs\Job;

class ProcessSQS implements HandlerContract
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @param Job $job
     * @param null $data
     * @return void
     */
    public function handle(Job $job, $data = null)
    {
        // process
        // you can access job payload via $data
				// $job->delete();
    }
}

```

### Origin
This is an enhancement to the unmaintained Laravel External Queue package (kristianedlund/laravel-external-queue)

### License

[MIT license](http://opensource.org/licenses/MIT).