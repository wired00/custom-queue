# Laravel Custom Queues

Laravel queues work great internally when jobs are both pushed (`SomeJob::dispatch()`) and then fetched via Laravel queue workers.

But what if jobs are pushed to a queue from an external service or message broker other than Laravel? For example, AWS S3 bucket > SNS > SQS? In those cases, Laravel queue workers do not recognise the payload, cannot parse it and break because the job payload is missing the expected `job` and `data` attributes etc.

`CustomQueue` aims to solve this issue. It will fetch a job,  re-packages the payload to a format which Laravel recognises and then process via a user specified job handler.

Payload without custom-queue:
```  
  {
  "Records": [
    {
      "eventVersion": "2.1",
      "eventSource": "aws:s3",
      "awsRegion": "ap-southeast-2",
      "eventTime": "2019-03-22T06:31:40.395Z",
      "eventName": "ObjectCreated:Put",
      "userIdentity": {
        "principalId": "AWS:blahblah:blah"
      },
      "requestParameters": {
        "sourceIPAddress": "10.10.10.10"
      },
      "responseElements": {
        "x-amz-request-id": "C53F65ECD63F53F8",
        "x-amz-id-2": "blah="
      },
      "s3": {
        "s3SchemaVersion": "1.0",
        "configurationId": "folder-name",
        "bucket": {
          "name": "bucket-name",
          "ownerIdentity": {
            "principalId": "A2XHNNJ3IERBDC"
          },
          "arn": "arn:aws:s3:::bucket-name"
        },
        "object": {
          "key": "file-drop/droptest.csv",
          "size": 12,
          "eTag": "tagid",
          "sequencer": "005C94814C54E35D75"
        }
      }
    }
  ]
}
```

Payload with custom-queue:
```  
{
  "type": "job",
  "job": "custom-sqs",
  "data": "{\"Records\":[{\"eventVersion\":\"2.1\",\"eventSource\":\"aws:s3\",\"awsRegion\":\"ap-southeast-2\",\"eventTime\":\"2019-03-22T06:31:40.395Z\",\"eventName\":\"ObjectCreated:Put\",\"userIdentity\":{\"principalId\":\"AWS:blahblah:blah\"},\"requestParameters\":{\"sourceIPAddress\":\"10.10.10.10\"},\"responseElements\":{\"x-amz-request-id\":\"C53F65ECD63F53F8\",\"x-amz-id-2\":\"blah=\"},\"s3\":{\"s3SchemaVersion\":\"1.0\",\"configurationId\":\"folder-name\",\"bucket\":{\"name\":\"bucket-name\",\"ownerIdentity\":{\"principalId\":\"A2XHNNJ3IERBDC\"},\"arn\":\"arn:aws:s3:::bucket-name\"},\"object\":{\"key\":\"file-drop/droptest.csv\",\"size\":12,\"eTag\":\"tagid\",\"sequencer\":\"005C94814C54E35D75\"}}}]}"
}
```
**Note: ** `job: custom-sqs` is designating a job handler to be used to process the job. `data` is simply the original payload.

## Installation

Install package via composer
```
composer require wired00/custom-queue
```

Publish the `CustomQueue` config file into your project
```
vendor:publish
```
See details on configuring `customqueue.php` below

## Usage

### Configuration

#### queue.php
Setup a custom external SQS connection

```
        'custom-sqs' => [
            'driver' => 'custom-sqs',
            'key' => env('AWS_ACCESS_KEY_ID', 'your-public-key'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', 'your-secret-key'),
            'queue' => env('SQS_QUEUE', 'your-queue-url'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],
```

Set all these values from your Laravel `.env`. I.e: 

```
AWS_ACCESS_KEY_ID=ASIAWMC25A2L7MDO6NGA
AWS_SECRET_ACCESS_KEY=3qZLVRShxQvx2xbTSKD5bllObtwHNH3O/9NqvFNc
AWS_SECURITY_TOKEN=YOUR-AWS-SECURITY-TOKEN
SQS_QUEUE=https://sqs.ap-southeast-2.amazonaws.com/123/your-sqs-queue-name
AWS_DEFAULT_REGION=ap-southeast-2
QUEUE_CONNECTION=custom-sqs
```

#### customqueue.php
`customqueue.php` config file simply contains a mapping between handler class path and an identifier.

The example contains identifier `custom-sqs` and class path `App\Jobs\ProcessSQS::class`. This specifies that the job payload when fetched from a queue will be appended with a key-value of `job: App\Jobs\ProcessSQS::class` whenever a connection type of `custom-sqs` is processed. The job will then process via  `ProcessSQS->handle()`. 

Currently CustomQueue only supports custom SQS fetching but in future it might support RabbitMQ and Redis. In those cases `customqueue.php` would include identifiers such as `custom-redis` and `custom-rabbitmq`.

### Handler files

Handler files are those referenced via the class path within `customqueue.php`. They must implement `Wired00\CustomQueue\Contracts\CustomQueueJobHandler`.

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
**Note:** `handle()` accepts the current job and importantly `$data` which contains the job payload popped from the SQS queue for example.

### License
This is built upon the unmaintained, and non-functional, Laravel External Queue package (kristianedlund/laravel-external-queue)

It is of MIT license do what you want with it. All care no responsibility.
[MIT license](http://opensource.org/licenses/MIT).