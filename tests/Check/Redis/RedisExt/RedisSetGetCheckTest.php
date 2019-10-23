<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check\Redis\RedisExt;

use FiveLab\Component\Diagnostic\Check\Redis\RedisExt\RedisSetGetCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractRedisTestCase;

class RedisSetGetCheckTest extends AbstractRedisTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithRedis()) {
            self::markTestSkipped('The Redis is not configured.');
        }

        if (!\class_exists(\Redis::class)) {
            self::markTestSkipped('The ext-redis not installed.');
        }
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $check = new RedisSetGetCheck(
            $this->getRedisHost(),
            $this->getRedisPort(),
            $this->getRedisPassword()
        );

        $result = $check->check();

        self::assertEquals(new Success('Success connect to Redis and SET/GET from Redis.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new RedisSetGetCheck(
            $this->getRedisHost(),
            $this->getRedisPort(),
            $this->getRedisPassword()
        );

        self::assertEquals([
            'host' => $this->getRedisHost(),
            'port' => $this->getRedisPort(),
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldFailIfHostIsWrong(): void
    {
        $check = new RedisSetGetCheck(
            $this->getRedisHost().'some',
            $this->getRedisPort(),
            $this->getRedisPassword()
        );

        $result = $check->check();

        self::assertEquals(new Failure('Cannot connect to Redis: php_network_getaddresses: getaddrinfo failed: Name or service not known.'), $result);
    }
}
