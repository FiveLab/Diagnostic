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
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check connect to RabbitMQ
 */
class RabbitMqConnectionCheck implements CheckInterface
{
    /**
     * @var RabbitMqConnectionParameters
     */
    private $connectionParameters;

    /**
     * Constructor.
     *
     * @param RabbitMqConnectionParameters $connectionParameters
     */
    public function __construct(RabbitMqConnectionParameters $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(\AMQPConnection::class)) {
            return new Failure('The ext-amqp not installed.');
        }

        $connectionParameters = [
            'host'            => $this->connectionParameters->getHost(),
            'port'            => $this->connectionParameters->getPort(),
            'vhost'           => $this->connectionParameters->getVhost(),
            'login'           => $this->connectionParameters->getUsername(),
            'password'        => $this->connectionParameters->getPassword(),
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
            'vhost' => $this->connectionParameters->getVhost(),
        ];
    }
}
