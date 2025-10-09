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

namespace FiveLab\Component\Diagnostic\Tests\Check\Redis\Predis;

use FiveLab\Component\Diagnostic\Check\Redis\Predis\PredisPingPongCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRedisTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class PredisPingPongCheckTest extends AbstractRedisTestCase
{
    protected function setUp(): void
    {
        if (!$this->canTestingWithRedis()) {
            self::markTestSkipped('The Redis is not configured.');
        }

        if (!\class_exists(\Redis::class)) {
            self::markTestSkipped('The ext-redis not installed.');
        }
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $check = new PredisPingPongCheck($this->getDsn());

        $result = $check->check();

        self::assertEquals(new Success('Success connect to Redis.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new PredisPingPongCheck($this->getDsn());

        self::assertEquals([
            'dsn' => $this->getDsn()->getDsn(path: true, maskedPassword: true),
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldFailIfDbIsNotNumeric(): void
    {
        $check = new PredisPingPongCheck(new Dsn(
            'tcp',
            $this->getRedisHost(),
            '/abc',
            $this->getRedisPort(),
            password: $this->getRedisPassword()
        ));

        $result = $check->check();

        self::assertEquals(new Failure('Cannot connect to Redis: The database name must be numeric.'), $result);
    }

    #[Test]
    public function shouldFailIfHostIsWrong(): void
    {
        $check = new PredisPingPongCheck(new Dsn(
            'tcp',
            $this->getRedisHost().'some',
            '/0',
            $this->getRedisPort(),
            password: $this->getRedisPassword()
        ));

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Cannot connect to Redis: php_network_getaddresses:', $result->message);
    }
}
