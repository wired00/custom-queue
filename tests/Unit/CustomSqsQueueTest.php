<?php

namespace Tests\Unit;

use Aws\Sqs\SqsClient;
use Orchestra\Testbench\TestCase as TestCase;
use Tests\Jobs\Handlers\ProcessSQS;
use Wired00\CustomQueue\CustomSqsQueue;

class CustomSqsQueueTest extends TestCase
{
    private $queueName;
    private $account;
    private $mockedPayload;
    private $mockedData;
    private $mockedJob;
    private $mockedMessageId;
    private $mockedReceiptHandle;
    private $queueUrl;

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

        $this->account = '1234567891011';
        $this->queueName = 'custom-sqs';

        $this->queueUrl = 'https://sqs.someregion.amazonaws.com/'.$this->account.'/'.$this->queueName;
        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData]);
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';
    }

    /**
     * When job popped from SQS Queue, it should be job of correct type
     *
     * @test
     */
    public function shouldHandlePoppingCorrectMessageFromSqsQueue()
    {
        $fakeReceiveMessageResponseModel =
            [
                'Messages' => [
                    0 => [
                        'Body'          => $this->mockedPayload,
                        'MD5OfBody'     => md5($this->mockedPayload),
                        'ReceiptHandle' => $this->mockedReceiptHandle,
                        'MessageId'     => $this->mockedMessageId,
                    ],
                ],

            ];

        $mockedSqsClient = \Mockery::mock(SqsClient::class);
        $mockedSqsClient
            ->shouldReceive('receiveMessage')
            ->once()
            ->with(
                [
                    'QueueUrl'       => $this->queueUrl,
                    'AttributeNames' => ['ApproximateReceiveCount'],
                ]
            )
            ->andReturn($fakeReceiveMessageResponseModel);
        $sqsQueue = new CustomSqsQueue($mockedSqsClient, $this->queueName);

        $result = $sqsQueue->pop($this->queueUrl);

        $this->assertInstanceOf('Wired00\CustomQueue\Jobs\CustomSqsJob', $result);
    }

    /**
     * Should handle popped job when SQS Queue is empty
     *
     * @test
     */
    public function shouldHandleWhenNoMessagesPoppedFromSqsQueue()
    {
        $fakeReceiveMessageResponseModel = ['Messages' => []];

        $mockedSqsClient = \Mockery::mock(SqsClient::class);
        $mockedSqsClient
            ->shouldReceive('receiveMessage')
            ->once()
            ->with(
                [
                    'QueueUrl'       => $this->queueUrl,
                    'AttributeNames' => ['ApproximateReceiveCount'],
                ]
            )
            ->andReturn($fakeReceiveMessageResponseModel);
        $sqsQueue = new CustomSqsQueue($mockedSqsClient, $this->queueName);

        $result = $sqsQueue->pop($this->queueUrl);

        $this->assertEquals(null, $result);
    }
}
