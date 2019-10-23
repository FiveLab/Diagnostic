<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check;

use PHPUnit\Framework\TestCase;

abstract class AbstractRedisTestCase extends TestCase
{
    /**
     * Get redis host
     *
     * @return string|null
     */
    protected function getRedisHost(): ?string
    {
        return \getenv('REDIS_HOST') ?: null;
    }

    /**
     * Get redis port
     *
     * @return int
     */
    protected function getRedisPort(): int
    {
        return \getenv('REDIS_PORT') ? (int) \getenv('REDIS_PORT') : 6379;
    }

    /**
     * Get redis password
     *
     * @return string|null
     */
    protected function getRedisPassword(): ?string
    {
        return \getenv('REDIS_PASSWORD') ?: null;
    }

    /**
     * Is can testing with redis?
     *
     * @return bool
     */
    protected function canTestingWithRedis(): bool
    {
        return (bool) $this->getRedisHost();
    }
}
