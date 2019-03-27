<?php

//use Guzzle\Service\Resource\Model;
use PHPUnit\Framework\TestCase;

class CustomSqsQueueTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function setUp()
    {
        // Use Mockery to mock the SqsClient
        $this->sqs = Mockery::mock('Aws\Sqs\SqsClient');
        $this->account = '1234567891011';
        $this->queueName = 'emails';
        $this->baseUrl = 'https://sqs.someregion.amazonaws.com';

        // This is how the modified getQueue builds the queueUrl
        $this->queueUrl = $this->baseUrl . '/' . $this->account . '/' . $this->queueName;
        $this->mockedJob = 'foo';
        $this->mockedData = array('data');
        $this->mockedPayload = json_encode(array('job' => $this->mockedJob, 'data' => $this->mockedData));
        $this->mockedDelay = 10;
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';
        $this->mockedSendMessageResponseModel =
            array(
                'Body' => $this->mockedPayload,
                'MD5OfBody' => md5($this->mockedPayload),
                'ReceiptHandle' => $this->mockedReceiptHandle,
                'MessageId' => $this->mockedMessageId,
                'Attributes' => array('ApproximateReceiveCount' => 1)

        );
        $this->mockedReceiveMessageResponseModel =
            array(
                'Messages' => array(
                    0 => array(
                        'Body' => $this->mockedPayload,
                        'MD5OfBody' => md5($this->mockedPayload),
                        'ReceiptHandle' => $this->mockedReceiptHandle,
                        'MessageId' => $this->mockedMessageId
                    )
                )

        );
    }

    public function testPopProperlyPopsJobOffOfSqs()
    {
        $queue = \Mockery::mock(Wired00\CustomQueue\CustomSqsQueue::class);
        $queue
            ->shouldreceive('getQueue')
            ->andReturn($this->sqs, $this->queueName, $this->account);
        $queue->setContainer(Mockery::mock('Illuminate\Container\Container'));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will(
            $this->returnValue($this->queueUrl)
        );

        $this->sqs
            ->shouldReceive('receiveMessage')
            ->once()
            ->with(
                array(
                    'QueueUrl' => $this->queueUrl,
                    'AttributeNames' => array('ApproximateReceiveCount')
                )
            )
            ->andReturn($this->mockedReceiveMessageResponseModel);

        $result = $queue->pop($this->queueName);
        $this->assertInstanceOf('Wired00\CustomQueue\Jobs\ExternalSqsJob', $result);
    }

    public function testPopProperlyPopsJobOfEmptyQueue()
    {
        // Overwrite the number of messages
        $this->mockedReceiveMessageResponseModel = array('Messages' => []);

        $queue = $this->getMock(
            'Wired00\CustomQueue\ExternalSqsQueue',
            array('getQueue'),
            array($this->sqs, $this->queueName, $this->account)
        );
        $queue->setContainer(Mockery::mock('Illuminate\Container\Container'));
        $queue->expects($this->once())->method('getQueue')->with($this->queueName)->will(
            $this->returnValue($this->queueUrl)
        );

        $this->sqs
            ->shouldReceive('receiveMessage')
            ->once()
            ->with(
                array(
                    'QueueUrl' => $this->queueUrl,
                    'AttributeNames' => array('ApproximateReceiveCount')
                )
            )
            ->andReturn($this->mockedReceiveMessageResponseModel);

        $result = $queue->pop($this->queueName);
        $this->assertEquals(null, $result);
    }
}
