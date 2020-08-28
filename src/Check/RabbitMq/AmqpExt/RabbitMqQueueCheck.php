<?php

declare(strict_types=1);

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt;

use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;

/**
 * RabbitMQ queue specific checks
 */
class RabbitMqQueueCheck extends AbstractRabbitMqCheck
{
    /**
     * @var string
     */
    private $queueName;

    /**
     * @var int|null
     */
    private $maxMessages;

    /**
     * must be between 0 and 100
     * @var int
     */
    private $maxWarningPercentage;

    /**
     * @var int|null
     */
    private $minMessages;

    /**
     * @param RabbitMqConnectionParameters $connectionParameters
     * @param string                       $queueName
     */
    public function __construct(RabbitMqConnectionParameters $connectionParameters, string $queueName)
    {
        parent::__construct($connectionParameters);

        $this->queueName = $queueName;
    }

    /**
     * @param int $maxMessages
     */
    public function setMaxMessages(int $maxMessages): void
    {
        $this->maxMessages = $maxMessages;
    }

    /**
     * @param int $maxWarningPercentage
     */
    public function setMaxWarningPercentage(int $maxWarningPercentage): void
    {
        if ($maxWarningPercentage < 0 || $maxWarningPercentage > 100) {
            throw new \InvalidArgumentException('$maxWarningPercentage must be between 0 and 100');
        }
        $this->maxWarningPercentage = $maxWarningPercentage;
    }

    /**
     * @param int $minMessages
     */
    public function setMinMessages(int $minMessages): void
    {
        $this->minMessages = $minMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $failure = $this->connect();

        if ($failure) {
            return $failure;
        }

        $channel = new \AMQPChannel($this->connection);
        $queue = new \AMQPQueue($channel);
        $queue->setFlags(AMQP_PASSIVE);
        $queue->setName($this->queueName);
        $messageCount = $queue->declareQueue();

        if (is_int($this->maxMessages)) {
            switch (true) {
                case $messageCount > $this->maxMessages:
                    return new Failure(
                        sprintf(
                            '%d messages found! Max allowed %d for queue %s',
                            $messageCount,
                            $this->maxMessages,
                            $this->queueName
                        )
                    );
                case $this->maxWarningPercentage && ($messageCount > ($this->maxMessages * $this->maxWarningPercentage) / 100):
                    return new Warning(
                        sprintf(
                            'Warning! %d messages found. Max %d for queue %s',
                            $messageCount,
                            $this->maxMessages,
                            $this->queueName
                        )
                    );
            }
        }

        if ($this->minMessages && $messageCount < $this->minMessages) {
            return new Failure(
                sprintf(
                    '%d messages found! Minimum required %d for queue %s',
                    $messageCount,
                    $this->minMessages,
                    $this->queueName
                )
            );
        }

        return new Success(sprintf('There are %d messages in queue %s', $messageCount, $this->queueName));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return array_merge(
            parent::getExtraParameters(),
            [
                'queue'        => $this->queueName,
                'max_messages' => $this->maxMessages,
            ]
        );
    }
}
