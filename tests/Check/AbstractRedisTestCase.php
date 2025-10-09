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

use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\TestCase;

abstract class AbstractRedisTestCase extends TestCase
{
    protected function getDsn(): Dsn
    {
        return new Dsn(
            'tcp',
            $this->getRedisHost(),
            $this->getRedisDatabase() !== null ? \sprintf('/%d', $this->getRedisDatabase()) : '/',
            $this->getRedisPort(),
            password: $this->getRedisPassword()
        );
    }

    protected function getRedisHost(): ?string
    {
        return \getenv('REDIS_HOST') ?: null;
    }

    protected function getRedisPort(): int
    {
        return \getenv('REDIS_PORT') ? (int) \getenv('REDIS_PORT') : 6379;
    }

    protected function getRedisPassword(): ?string
    {
        return \getenv('REDIS_PASSWORD') ?: null;
    }

    protected function getRedisDatabase(): ?int
    {
        return \getenv('REDIS_DB') ? (int) \getenv('REDIS_DB') : null;
    }

    protected function canTestingWithRedis(): bool
    {
        return (bool) $this->getRedisHost();
    }
}
