<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check connect to RabbitMQ
 */
readonly class RabbitMqConnectionCheck implements CheckInterface
{
    /**
     * Constructor.
     *
     * @param RabbitMqConnectionParameters $connectionParameters
     */
    public function __construct(private RabbitMqConnectionParameters $connectionParameters)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        if (!\class_exists(\AMQPConnection::class)) {
            return new Failure('The ext-amqp not installed.');
        }

        $connectionParameters = [
            'host'            => $this->connectionParameters->host,
            'port'            => $this->connectionParameters->port,
            'vhost'           => $this->connectionParameters->vhost,
            'login'           => $this->connectionParameters->username,
            'password'        => $this->connectionParameters->password,
            'connect_timeout' => 5,
        ];

        $connection = new \AMQPConnection($connectionParameters);

        try {
            $connection->connect();
        } catch (\AMQPConnectionException $e) {
            return new Failure($e->getMessage());
        }

        return new Success('Success connect to RabbitMQ');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'dsn'   => $this->connectionParameters->getDsn(false, true),
            'vhost' => $this->connectionParameters->vhost,
        ];
    }
}
