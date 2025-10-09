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

use FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRabbitMqTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class RabbitMqManagementCheckTest extends AbstractRabbitMqTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->canTestingWithRabbitMq()) {
            self::markTestSkipped('The RabbitMQ is not configured.');
        }
    }

    #[Test]
    public function shouldSuccessConnect(): void
    {
        $dsn = $this->getRabbitMqManagementDsn();

        $check = new RabbitMqManagementCheck($dsn);
        $result = $check->check();

        self::assertEquals(new Success('Success connect to RabbitMQ Management API.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtra(): void
    {
        $dsn = $this->getRabbitMqDsn();

        $check = new RabbitMqManagementCheck($dsn);

        self::assertEquals([
            'dsn' => $dsn->getDsn(path: true, maskedPassword: true),
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
            'some-foo-bar'
        );

        $check = new RabbitMqManagementCheck($dsn);
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

        $check = new RabbitMqManagementCheck($dsn);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Fail connect to RabbitMQ Management API. Error: cURL error 6: Could not resolve host: some-foo-bar', $result->message);
    }
}
