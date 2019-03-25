<?php

use Wired00\CustomQueue\Jobs\ExternalSqsJob;
use Illuminate\Queue\Jobs\Job;
use Aws\Sqs\SqsClient;
use Guzzle\Common\Collection;
use Aws\Common\Signature\SignatureV4;
use Aws\Common\Credentials\Credentials;


/**
 * Help functions for the test
 */

function config()
{
    return 'TestJob';
}

class ExternalSqsJobTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function setUp()
    {
        $this->key = 'AMAZONSQSKEY';
        $this->secret = 'AmAz0n+SqSsEcReT+aLpHaNuM3R1CsTr1nG';
        $this->service = 'sqs';
        $this->region = 'someregion';
        $this->account = '1234567891011';
        $this->queueName = 'emails';
        $this->baseUrl = 'https://sqs.someregion.amazonaws.com';
        // The Aws\Common\AbstractClient needs these three constructor parameters
        $this->credentials = new Credentials($this->key, $this->secret);
        $this->signature = new SignatureV4($this->service, $this->region);
        $this->config = new Collection();
        // This is how the modified getQueue builds the queueUrl
        $this->queueUrl = $this->baseUrl . '/' . $this->account . '/' . $this->queueName;
        // Get a mock of the SqsClient
        $this->mockedSqsClient = $this->getMock(
            'Aws\Sqs\SqsClient',
            array('deleteMessage'),
            array($this->credentials, $this->signature, $this->config)
        );
        // Use Mockery to mock the IoC Container
        $this->mockedContainer = Mockery::mock('Illuminate\Container\Container');
        $this->mockedJob = 'foo';
        $this->mockedData = array('data');
        $this->mockedPayload = json_encode(
            array('job' => $this->mockedJob, 'data' => $this->mockedData, 'attempts' => 1)
        );
        $this->mockedMessageId = 'e3cd03ee-59a3-4ad8-b0aa-ee2e3808ac81';
        $this->mockedReceiptHandle = '0NNAq8PwvXuWv5gMtS9DJ8qEdyiUwbAjpp45w2m6M4SJ1Y+PxCh7R930NRB8ylSacEmoSnW18bgd4nK\/O6ctE+VFVul4eD23mA07vVoSnPI4F\/voI1eNCp6Iax0ktGmhlNVzBwaZHEr91BRtqTRM3QKd2ASF8u+IQaSwyl\/DGK+P1+dqUOodvOVtExJwdyDLy1glZVgm85Yw9Jf5yZEEErqRwzYz\/qSigdvW4sm2l7e4phRol\/+IjMtovOyH\/ukueYdlVbQ4OshQLENhUKe7RNN5i6bE\/e5x9bnPhfj2gbM';
        $this->mockedJobData = array(
            'Body' => $this->mockedPayload,
            'MD5OfBody' => md5($this->mockedPayload),
            'ReceiptHandle' => $this->mockedReceiptHandle,
            'MessageId' => $this->mockedMessageId,
            'Attributes' => array('ApproximateReceiveCount' => 1)
        );
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testHandlerNotDefined()
    {
        //Act
        $job = new ExternalSqsJob(
            $this->mockedContainer,
            $this->mockedSqsClient,
            $this->queueUrl,
            $this->mockedJobData
        );

        $job->fire();
    }

    public function testSpawnJob()
    {
        //Arrange
        $testJob = \Mockery::mock('overload:TestJob', '\Wired00\CustomQueue\Contracts\ExternalQueueJobHandler');
        $testJob->shouldReceive('handle')->once()->with(\Mockery::any(), $this->mockedData);

        //Act
        $job = new ExternalSqsJob(
            $this->mockedContainer,
            $this->mockedSqsClient,
            $this->queueUrl,
            $this->mockedJobData
        );

        $job->fire();
    }
}
