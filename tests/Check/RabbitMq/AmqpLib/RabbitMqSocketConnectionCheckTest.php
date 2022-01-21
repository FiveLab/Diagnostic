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

namespace FiveLab\Component\Diagnostic\Tests\Check\RabbitMq\AmqpLib;

use FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpLib\RabbitMqSocketConnectionCheck;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqSocketConnectionCheckTest extends AbstractRabbitMqTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        if (!\extension_loaded('sockets')) {
            self::markTestSkipped('ext-sockets is not installed.');
        }

        if (!\class_exists(AMQPStreamConnection::class)) {
            self::markTestSkipped('php-amqplib/php-amqplib is not installed.');
        }
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new RabbitMqSocketConnectionCheck($this->getRabbitMqConnectionParameters());

        self::assertEquals([
            'dsn'   => $this->getRabbitMqConnectionParameters()->getDsn(false, true),
            'vhost' => '/',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldReturnOkIfSuccessConnect(): void
    {
        $check = new RabbitMqSocketConnectionCheck($this->getRabbitMqConnectionParameters());

        $result = $check->check();

        self::assertEquals(new Success('Successfully connected to RabbitMQ'), $result);
    }

    /**
     * @test
     */
    public function shouldReturnFailIfPasswordIsWrong(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            $this->getRabbitMqHost(),
            $this->getRabbitMqPort(),
            $this->getRabbitMqLogin(),
            \uniqid(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqSocketConnectionCheck($connectionParameters);

        $result = $check->check();

        self::assertEquals(
            new Failure('ACCESS_REFUSED - Login was refused using authentication mechanism AMQPLAIN. For details see the broker logfile.(0, 0)'),
            $result
        );
    }

    /**
     * @test
     */
    public function shouldFailIfHostIsDown(): void
    {
        $connectionParameters = new RabbitMqConnectionParameters(
            \uniqid(),
            $this->getRabbitMqPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword(),
            $this->getRabbitMqVhost()
        );

        $check = new RabbitMqSocketConnectionCheck($connectionParameters);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString('Error Connecting to server', $result->getMessage());
    }
}
