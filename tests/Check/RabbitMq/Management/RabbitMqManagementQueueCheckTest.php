<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FiveLab\Component\Diagnostic\Tests\Check\RabbitMq\Management;

use FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementQueueCheck;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;

class RabbitMqManagementQueueCheckTest extends AbstractRabbitMqTestCase
{
    /**
     * @var string
     */
    private $queueName;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        $this->queueName = 'test_'.\uniqid((string) \random_int(0, PHP_INT_MAX), true);
        $this->declareQueue($this->queueName);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if ($this->canTestingWithRabbitMq()) {
            $this->deleteAllQueues();
        }
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);
        $result = $check->check();

        self::assertEquals(new Success('Success check queue via RabbitMQ Management API.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtra(): void
    {
        $connectionParameters = $this->getRabbitMqManagementConnectionParameters();

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);

        self::assertEquals([
            'dsn'                    => $connectionParameters->getDsn(true, true),
            'vhost'                  => $connectionParameters->getVhost(),
            'queue'                  => $this->queueName,
            'max_messages'           => null,
            'min_messages'           => null,
            'max_warning_percentage' => null,
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailIfPasswordIsWrong(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            'some-foo-bar',
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);
        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to RabbitMQ Management API. Return wrong status code - 401.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfHostIsWrong(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            'some-foo-bar',
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to RabbitMQ Management API. Error: cURL error 6: Could not resolve host: some-foo-bar', $result->getMessage());
    }

    /**
     * @test
     */
    public function shouldFailIfQueueNotFound(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName.'a');
        $result = $check->check();

        self::assertEquals(new Failure('Queue was not found.'), $result);
    }

    /**
     * @test
     * @dataProvider lengthCheckProvider
     *
     * @param string   $resultClass
     * @param string   $resultText
     * @param int      $actualLength
     * @param int|null $max
     * @param int|null $percentage
     * @param int|null $min
     *
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function shouldCheckQueueLength(string $resultClass, string $resultText, int $actualLength, int $max = null, int $percentage = null, int $min = null)
    {
        $this->publishDummyMessagesToQueue($this->queueName, $actualLength);

        $connectionParameters = new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqManagementQueueCheck($connectionParameters, $this->queueName, $max, $min, $percentage);

        sleep(6);   // RabbitMQ-management does not update stats fast
        $result = $check->check();

        self::assertInstanceOf($resultClass, $result, $result->getMessage());
        self::assertStringContainsString($resultText, $result->getMessage());
    }

    /**
     * @return array[]
     */
    public function lengthCheckProvider(): array
    {
        return [
            'below maximum'  => [
                Success::class,
                'Success check queue via RabbitMQ Management API.',
                4,
                5,
                null,
                null,
            ],
            'above minimum'  => [
                Success::class,
                'Success check queue via RabbitMQ Management API.',
                6,
                null,
                null,
                5,
            ],
            'above maximum'  => [
                Failure::class,
                '6 messages found! Max allowed 5 for queue '.$this->queueName,
                6,
                5,
                null,
                1,
            ],
            'warning amount' => [
                Warning::class,
                'Warning! 6 messages found. Max 10 for queue '.$this->queueName,
                6,
                10,
                50,
                1,
            ],
            'below minimum'  => [
                Failure::class,
                '2 messages found! Minimum required 5 for queue '.$this->queueName,
                2,
                10,
                null,
                5,
            ],
        ];
    }

    /**
     * @param string $queueName
     * @param int    $amount
     *
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    private function publishDummyMessagesToQueue(string $queueName, int $amount = 1): void
    {
        $connection = new \AMQPConnection([
            'host'     => $this->getRabbitMqHost(),
            'port'     => $this->getRabbitMqPort(),
            'vhost'    => $this->getRabbitMqVhost(),
            'login'    => $this->getRabbitMqLogin(),
            'password' => $this->getRabbitMqPassword(),
        ]);
        $connection->connect();

        $channel = new \AMQPChannel($connection);
        $exchange = new \AMQPExchange($channel);
        $exchange->setName('');

        for ($i = 0; $i < $amount; $i++) {
            $exchange->publish('{}', $queueName);
        }
    }
}
