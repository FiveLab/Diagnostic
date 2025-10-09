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

namespace FiveLab\Component\Diagnostic\Tests\Check\Redis\RedisExt;

use FiveLab\Component\Diagnostic\Check\Redis\RedisExt\RedisPingPongCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRedisTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class RedisPingPongCheckTest extends AbstractRedisTestCase
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
        $check = new RedisPingPongCheck($this->getDsn());

        $result = $check->check();

        self::assertEquals(new Success('Success connect to Redis.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new RedisPingPongCheck($this->getDsn());

        self::assertEquals([
            'dsn' => $this->getDsn()->getDsn(path: true, maskedPassword: true),
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldFailIfDbIsNotNumeric(): void
    {
        $check = new RedisPingPongCheck(new Dsn(
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
        $check = new RedisPingPongCheck(new Dsn(
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
