<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check;

use PHPUnit\Framework\TestCase;

abstract class AbstractDatabaseTestCase extends TestCase
{
    /**
     * Get the database host
     *
     * @return string|null
     */
    protected function getDatabaseHost(): ?string
    {
        return \getenv('DATABASE_HOST') ?: null;
    }

    /**
     * Get database port
     *
     * @return int|null
     */
    protected function getDatabasePort(): int
    {
        return \getenv('DATABASE_PORT') ? (int) \getenv('DATABASE_PORT') : 3306;
    }

    /**
     * Get the user for connect to database
     *
     * @return string
     */
    protected function getDatabaseUser(): string
    {
        return \getenv('DATABASE_USER') ? \getenv('DATABASE_USER') : 'root';
    }

    /**
     * Get the password for connect to database
     *
     * @return string
     */
    protected function getDatabasePassword(): string
    {
        return \getenv('DATABASE_PASSWORD') ? \getenv('DATABASE_PASSWORD') : '';
    }

    /**
     * Get the name of database
     *
     * @return string
     */
    protected function getDatabaseName(): string
    {
        return \getenv('DATABASE_NAME') ? \getenv('DATABASE_NAME') : 'diagnostic';
    }

    /**
     * Can testing with database?
     *
     * @return bool
     */
    protected function canTestingWithDatabase(): bool
    {
        return
            $this->getDatabaseHost() &&
            $this->getDatabasePort() &&
            $this->getDatabaseUser() &&
            $this->getDatabaseName();
    }
}
