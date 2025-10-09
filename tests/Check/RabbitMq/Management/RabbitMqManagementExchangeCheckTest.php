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

namespace FiveLab\Component\Diagnostic\Tests\Check\RabbitMq\Management;

use FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementExchangeCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class RabbitMqManagementExchangeCheckTest extends AbstractRabbitMqTestCase
{
    private ?string $exchangeName = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }

        $this->exchangeName = 'test_'.\uniqid((string) \random_int(0, PHP_INT_MAX), true);
        $this->declareExchange($this->exchangeName, 'direct');
    }

    protected function tearDown(): void
    {
        if ($this->canTestingWithRabbitMq()) {
            $this->deleteAllExchanges();
        }
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $dsn = $this->getRabbitMqManagementDsn();

        $check = new RabbitMqManagementExchangeCheck($dsn, $this->exchangeName, 'direct');
        $result = $check->check();

        self::assertEquals(new Success('Success check exchange via RabbitMQ Management API.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtra(): void
    {
        $dsn = $this->getRabbitMqManagementDsn();

        $check = new RabbitMqManagementExchangeCheck($dsn, $this->exchangeName, 'direct');

        self::assertEquals([
            'dsn'         => $dsn->getDsn(path: true, maskedPassword: true),
            'exchange'    => $this->exchangeName,
            'type'        => 'direct',
            'actual type' => null,
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldFailIfPasswordIsWrong(): void
    {
        $dsn = new Dsn(
            'http',
            $this->getRabbitMqHost(),
            '/'.$this->getRabbitMqVhost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            'some-foo-bar',
        );

        $check = new RabbitMqManagementExchangeCheck($dsn, $this->exchangeName, 'direct');
        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to RabbitMQ Management API. Return wrong status code - 401.'), $result);
    }

    #[Test]
    public function shouldFailIfHostIsWrong(): void
    {
        $dsn = new Dsn(
            'http',
            'some-foo-bar',
            '/'.$this->getRabbitMqVhost(),
            $this->getRabbitMqManagementPort(),
            $this->getRabbitMqLogin(),
            $this->getRabbitMqPassword()
        );

        $check = new RabbitMqManagementExchangeCheck($dsn, $this->exchangeName, 'direct');
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to RabbitMQ Management API. Error: cURL error 6: Could not resolve host: some-foo-bar', $result->message);
    }

    #[Test]
    public function shouldFailIfExchangeNotFound(): void
    {
        $dsn = $this->getRabbitMqManagementDsn();

        $check = new RabbitMqManagementExchangeCheck($dsn, $this->exchangeName.'a', 'direct');
        $result = $check->check();

        self::assertEquals(new Failure('Exchange was not found.'), $result);
    }

    #[Test]
    public function shouldFailIfTypeIsInvalid(): void
    {
        $dsn = $this->getRabbitMqManagementDsn();

        $check = new RabbitMqManagementExchangeCheck($dsn, $this->exchangeName, 'fanout');
        $result = $check->check();

        self::assertEquals(new Failure('Invalid exchange types.'), $result);

        self::assertEquals([
            'dsn'         => $dsn->getDsn(path: true, maskedPassword: true),
            'exchange'    => $this->exchangeName,
            'type'        => 'fanout',
            'actual type' => 'direct',
        ], $check->getExtraParameters());
    }
}
