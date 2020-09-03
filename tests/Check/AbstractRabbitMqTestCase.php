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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use PHPUnit\Framework\TestCase;

abstract class AbstractRabbitMqTestCase extends TestCase
{
    /**
     * Declare exchange
     *
     * @param string $exchangeName
     * @param string $exchangeType
     */
    protected function declareExchange(string $exchangeName, string $exchangeType = 'direct'): void
    {
        $options = [
            'type'        => $exchangeType,
            'auto_delete' => false,
            'durable'     => true,
        ];

        $parameters = $this->getRabbitMqManagementConnectionParameters();

        $dsn = $parameters->getDsn(true, false);
        $url = \sprintf(
            '%s/api/exchanges/%s/%s',
            $dsn,
            \urlencode($parameters->getVhost()),
            \urlencode($exchangeName)
        );

        $context = \stream_context_create([
            'http' => [
                'method'  => 'PUT',
                'header'  => 'content-type:application/x-www-form-urlencoded',
                'content' => \json_encode($options),
            ],
        ]);

        \file_get_contents($url, false, $context);
    }

    /**
     * Delete all queues
     */
    protected function deleteAllExchanges(): void
    {
        $parameters = $this->getRabbitMqManagementConnectionParameters();

        $dsn = $parameters->getDsn(true, false);
        $url = \sprintf('%s/api/exchanges/%s', $dsn, \urlencode($parameters->getVhost()));

        $data = \json_decode(\file_get_contents($url), true);

        $deleteContext = \stream_context_create([
            'http' => [
                'method' => 'DELETE',
            ],
        ]);

        foreach ($data as $exchangeInfo) {
            if ('rmq-internal' === $exchangeInfo['user_who_performed_action']) {
                // It's a internal exchange.
                continue;
            }

            $url = \sprintf(
                '%s/api/exchanges/%s/%s',
                $dsn,
                \urlencode($parameters->getVhost()),
                \urlencode($exchangeInfo['name'])
            );

            \file_get_contents($url, false, $deleteContext);
        }
    }

    /**
     * Declare queue
     *
     * @param string $queueName
     */
    protected function declareQueue(string $queueName): void
    {
        $options = [
            'auto_delete' => false,
            'durable'     => true,
        ];

        $parameters = $this->getRabbitMqManagementConnectionParameters();

        $dsn = $parameters->getDsn(true, false);
        $url = \sprintf(
            '%s/api/queues/%s/%s',
            $dsn,
            \urlencode($parameters->getVhost()),
            \urlencode($queueName)
        );

        $context = \stream_context_create([
            'http' => [
                'method'  => 'PUT',
                'header'  => 'content-type:application/x-www-form-urlencoded',
                'content' => \json_encode($options),
            ],
        ]);

        \file_get_contents($url, false, $context);
    }

    /**
     * Delete all queues
     */
    protected function deleteAllQueues(): void
    {
        $parameters = $this->getRabbitMqManagementConnectionParameters();

        $dsn = $parameters->getDsn(true, false);
        $url = \sprintf('%s/api/queues/%s', $dsn, \urlencode($parameters->getVhost()));

        $data = \json_decode(\file_get_contents($url), true);

        $deleteContext = \stream_context_create([
            'http' => [
                'method' => 'DELETE',
            ],
        ]);

        foreach ($data as $queueInfo) {
            $url = \sprintf(
                '%s/api/queues/%s/%s',
                $dsn,
                \urlencode($parameters->getVhost()),
                \urlencode($queueInfo['name'])
            );

            \file_get_contents($url, false, $deleteContext);
        }
    }

    /**
     * Get RabbitMq Connection parameters
     *
     * @return RabbitMqConnectionParameters
     */
    protected function getRabbitMqConnectionParameters(): RabbitMqConnectionParameters
    {
        if (!$this->canTestingWithRabbitMq()) {
            throw new \LogicException('Can\'t get connection parameters. No configured.');
        }

        return new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost(),
            false
        );
    }

    /**
     * Get RabbitMQ Management connection parameters
     *
     * @return RabbitMqConnectionParameters
     */
    protected function getRabbitMqManagementConnectionParameters(): RabbitMqConnectionParameters
    {
        if (!$this->canTestingWithRabbitMq()) {
            throw new \LogicException('Can\'t get connection parameters. No configured.');
        }

        return new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost(),
            false
        );
    }

    /**
     * Get host for connect to RabbitMQ
     *
     * @return string
     */
    protected function getRabbitMqHost(): ?string
    {
        return \getenv('RABBITMQ_HOST') ?: null;
    }

    /**
     * Get port for connect to RabbitMQ
     *
     * @return int
     */
    protected function getRabbitMqPort(): int
    {
        return \getenv('RABBITMQ_PORT') ? (int) \getenv('RABBITMQ_PORT') : 5672;
    }

    /**
     * Get RabbitMQ management port
     *
     * @return int
     */
    public function getRabbitMqManagementPort(): int
    {
        return \getenv('RABBITMQ_MANAGEMENT_PORT') ? (int) \getenv('RABBITMQ_MANAGEMENT_PORT') : 15672;
    }

    /**
     * Get RabbitMQ virtual host
     *
     * @return string
     */
    protected function getRabbitMqVhost(): string
    {
        return \getenv('RABBITMQ_VHOST') ?: '/';
    }

    /**
     * Get the login for connect to RabbitMQ
     *
     * @return string
     */
    protected function getRabbitMqLogin(): string
    {
        return \getenv('RABBITMQ_LOGIN') ?: 'guest';
    }

    /**
     * Get the password for connect to RabbitMQ.
     *
     * @return string
     */
    protected function getRabbitMqPassword(): string
    {
        return \getenv('RABBITMQ_PASSWORD') ?: 'guest';
    }

    /**
     * Is can testing with RabbitMQ?
     *
     * @return bool
     */
    protected function canTestingWithRabbitMq(): bool
    {
        return (bool) $this->getRabbitMqHost();
    }
}
