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

use FiveLab\Component\Diagnostic\Check\Redis\Predis\PredisCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRedisTestCase;
use PHPUnit\Framework\Attributes\Test;

class PredisCheckTest extends AbstractRedisTestCase
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
        $check = new PredisCheck(
            $this->getRedisHost(),
            $this->getRedisPort(),
            $this->getRedisPassword()
        );

        $result = $check->check();

        self::assertEquals(new Success('Success connect to Redis.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new PredisCheck(
            $this->getRedisHost(),
            $this->getRedisPort(),
            $this->getRedisPassword()
        );

        self::assertEquals([
            'host' => $this->getRedisHost(),
            'port' => $this->getRedisPort(),
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldFailIfHostIsWrong(): void
    {
        $check = new PredisCheck(
            $this->getRedisHost().'some',
            $this->getRedisPort(),
            $this->getRedisPassword()
        );

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith('Cannot connect to Redis: php_network_getaddresses:', $result->message);
    }
}
