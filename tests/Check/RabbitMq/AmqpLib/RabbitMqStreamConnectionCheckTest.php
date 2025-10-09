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

use FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpLib\RabbitMqStreamConnectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\Attributes\Test;

class RabbitMqStreamConnectionCheckTest extends AbstractRabbitMqTestCase
{
    protected function setUp(): void
    {
        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        if (!\class_exists(AMQPStreamConnection::class)) {
            self::markTestSkipped('php-amqplib/php-amqplib is not installed.');
        }
    }

    #[Test]
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new RabbitMqStreamConnectionCheck($this->getRabbitMqDsn());

        self::assertEquals([
            'dsn' => $this->getRabbitMqDsn()->getDsn(path: true, maskedPassword: true),
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldReturnOkIfSuccessConnect(): void
    {
        $check = new RabbitMqStreamConnectionCheck($this->getRabbitMqDsn());

        $result = $check->check();

        self::assertEquals(new Success('Successfully connected to RabbitMQ'), $result);
    }

    #[Test]
    public function shouldReturnFailIfPasswordIsWrong(): void
    {
        $dsn = new Dsn(
            'tcp',
            $this->getRabbitMqHost(),
            '/'.$this->getRabbitMqVhost(),
            $this->getRabbitMqPort(),
            $this->getRabbitMqLogin(),
            \uniqid(),
        );

        $check = new RabbitMqStreamConnectionCheck($dsn);

        $result = $check->check();

        self::assertEquals(
            new Failure('ACCESS_REFUSED - Login was refused using authentication mechanism AMQPLAIN. For details see the broker logfile.(0, 0)'),
            $result
        );
    }

    #[Test]
    public function shouldFailIfHostIsDown(): void
    {
        $dsn = new Dsn(
            'tcp',
            \uniqid(),
            '/'.$this->getRabbitMqVhost(),
            $this->getRabbitMqPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword()
        );

        $check = new RabbitMqStreamConnectionCheck($dsn);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString('getaddrinfo', $result->message);
        self::assertStringContainsString('failed', $result->message);
    }
}
