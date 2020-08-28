<?php

declare(strict_types=1);

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;

/**
 * Initializes connection for AmqpExt checks
 */
abstract class AbstractRabbitMqCheck implements CheckInterface
{
    /**
     * @var \AMQPConnection|null
     */
    protected $connection;
    /**
     * @var RabbitMqConnectionParameters
     */
    private $connectionParameters;

    /**
     * @param RabbitMqConnectionParameters $connectionParameters
     */
    public function __construct(RabbitMqConnectionParameters $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'dsn'   => $this->connectionParameters->getDsn(false, true),
            'vhost' => $this->connectionParameters->getVhost(),
        ];
    }

    /**
     * Connects to RabbitMQ, returns null on success or @return Failure|null
     * @see Failure otherwise
     *
     * @return Failure|null
     */
    protected function connect(): ?Failure
    {
        if (!\class_exists(\AMQPConnection::class)) {
            return new Failure('The ext-amqp not installed.');
        }

        $this->connection = new \AMQPConnection($this->getConnectionParams());

        try {
            $this->connection->connect();
        } catch (\AMQPConnectionException $e) {
            return new Failure($e->getMessage());
        }

        return null;
    }

    /**
     * @return array array of params suitable for \AMQPConnection
     */
    protected function getConnectionParams(): array
    {
        return [
            'host'            => $this->connectionParameters->getHost(),
            'port'            => $this->connectionParameters->getPort(),
            'vhost'           => $this->connectionParameters->getVhost(),
            'login'           => $this->connectionParameters->getUsername(),
            'password'        => $this->connectionParameters->getPassword(),
            'connect_timeout' => 5,
        ];
    }
}
