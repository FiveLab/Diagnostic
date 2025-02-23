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

use PHPUnit\Framework\TestCase;

abstract class AbstractDatabaseTestCase extends TestCase
{
    protected function getDatabaseHost(): ?string
    {
        return \getenv('DATABASE_HOST') ?: null;
    }

    protected function getDatabasePort(): int
    {
        return \getenv('DATABASE_PORT') ? (int) \getenv('DATABASE_PORT') : 3306;
    }

    protected function getDatabaseUser(): string
    {
        return \getenv('DATABASE_USER') ? \getenv('DATABASE_USER') : 'root';
    }

    protected function getDatabasePassword(): string
    {
        return \getenv('DATABASE_PASSWORD') ? \getenv('DATABASE_PASSWORD') : '';
    }

    protected function getDatabaseName(): string
    {
        return \getenv('DATABASE_NAME') ? \getenv('DATABASE_NAME') : 'diagnostic';
    }

    protected function canTestingWithDatabase(): bool
    {
        return
            $this->getDatabaseHost() &&
            $this->getDatabasePort() &&
            $this->getDatabaseUser() &&
            $this->getDatabaseName();
    }
}
