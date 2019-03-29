<?php

namespace Tests\Unit\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Tests\Jobs\Handlers\ProcessSQS;
use Wired00\CustomQueue\Jobs\CustomSqsJob;
use Orchestra\Testbench\TestCase as TestCase;
use Wired00\CustomQueue\Factories\JobHandlerFactory;

class CustomSqsJobTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('customqueue.handlers.custom-sqs', ProcessSQS::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueName = 'emails';
        $this->account = '1234567891011';
        $this->queueUrl = $this->baseUrl.'/'.$this->account.'/'.$this->queueName;
        $this->mockedJob = 'foo';
        $this->mockedData = ['here is some data from queued job'];
        $this->mockedPayload = json_encode(
            [
                'job'      => $this->mockedJob,
                'data'     => $this->mockedData,
                'attempts' => 1,
            ]
        );
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';
        $this->mockedJobData = [
            'Body'          => $this->mockedPayload,
            'MD5OfBody'     => md5($this->mockedPayload),
            'ReceiptHandle' => $this->mockedReceiptHandle,
            'MessageId'     => $this->mockedMessageId,
            'Attributes'    => ['ApproximateReceiveCount' => 1],
        ];

        $this->mockedSqsClient = \Mockery::mock(SqsClient::class);
        $this->mockedContainer = \Mockery::mock(Container::class);
        $this->mockedJobHandler = \Mockery::mock(ProcessSQS::class);
        $this->mockedJobHandler
            ->shouldReceive('handle')
            ->once()
            ->with(\Mockery::any(), $this->mockedPayload);
        $this->mockedJobHandlerFactory = \Mockery::mock(JobHandlerFactory::class);
        $this->mockedJobHandlerFactory
            ->shouldReceive('create')
            ->andReturn($this->mockedJobHandler);
    }

    public function testJobHandlerCalled()
    {
        $job = new CustomSqsJob(
            $this->mockedContainer,
            $this->mockedSqsClient,
            $this->mockedJobData,
            'custom-sqs,',
            $this->queueUrl,
            $this->mockedJobHandlerFactory
        );

        $job->fire();
    }
}
