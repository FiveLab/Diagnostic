<?php

declare(strict_types=1);

namespace FiveLab\Component\Diagnostic\Tests\Check\RabbitMq\AmqpExt;

use FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt\RabbitMqQueueCheck;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;

class RabbitMqQueueCheckTest extends AbstractRabbitMqTestCase
{
    /**
     * @var string
     */
    private $queueName;

    /**
     * @var \AMQPQueue
     */
    private $queue;

    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        if (!\class_exists(\AMQPConnection::class)) {
            self::markTestSkipped('The ext-amqp not installed.');
        }

        $this->queueName = uniqid('test_queue', true);

        $this->connection = new \AMQPConnection([
            'host'     => $this->getRabbitMqHost(),
            'port'     => $this->getRabbitMqPort(),
            'vhost'    => $this->getRabbitMqVhost(),
            'login'    => $this->getRabbitMqLogin(),
            'password' => $this->getRabbitMqPassword(),
        ]);
        $this->connection->connect();
        $channel = new \AMQPChannel($this->connection);

        $this->queue = new \AMQPQueue($channel);
        $this->queue->setName($this->queueName);
        $this->queue->declareQueue();
    }

    /**
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     */
    protected function tearDown(): void
    {
        $this->queue->delete();

        parent::tearDown();
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
            $this->getRabbitMqPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqQueueCheck($connectionParameters, $this->queueName);

        if ($max) {
            $check->setMaxMessages($max);
        }

        if ($percentage) {
            $check->setMaxWarningPercentage($percentage);
        }

        if ($min) {
            $check->setMinMessages($min);
        }

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
                'There are 4 messages in queue test_queue',
                4,
                5,
                null,
                null,
            ],
            'above minimum'  => [
                Success::class,
                'There are 6 messages in queue test_queue',
                6,
                null,
                null,
                5,
            ],
            'above maximum'  => [
                Failure::class,
                '6 messages found! Max allowed 5 for queue test_queue',
                6,
                5,
                null,
                1,
            ],
            'warning amount' => [
                Warning::class,
                'Warning! 6 messages found. Max 10 for queue test_queue',
                6,
                10,
                50,
                1,
            ],
            'below minimum'  => [
                Failure::class,
                '2 messages found! Minimum required 5 for queue test_queue',
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
        $channel = new \AMQPChannel($this->connection);
        $exchange = new \AMQPExchange($channel);
        $exchange->setName('');

        for ($i = 0; $i < $amount; $i++) {
            $exchange->publish('{}', $queueName);
        }
    }
}
