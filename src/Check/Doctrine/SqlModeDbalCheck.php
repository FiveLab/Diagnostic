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

namespace FiveLab\Component\Diagnostic\Check\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check the SQL_MODE via doctrine dbal (use dbal connection).
 */
class SqlModeDbalCheck extends AbstractDbalCheck
{
    /**
     * @var array<int, mixed>|null
     */
    private ?array $actualSqlModes = null;

    /**
     * Constructor.
     *
     * @param DriverConnection|Connection $connection
     * @param string[]                    $expectedSqlModes
     * @param string[]                    $excludedSqlModes
     */
    public function __construct(DriverConnection|Connection $connection, private readonly array $expectedSqlModes = [], private readonly array $excludedSqlModes = [])
    {
        parent::__construct($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        try {
            $stmt = $this->connection->executeQuery('SELECT @@GLOBAL.sql_mode');
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to database. Throw exception: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        [$sqlMode] = $stmt->fetchNumeric(); // @phpstan-ignore-line

        $sqlModes = \explode(',', $sqlMode);
        $sqlModes = \array_map('\trim', $sqlModes);

        $this->actualSqlModes = $sqlModes;

        $sqlModes = \array_map('\strtolower', $sqlModes);

        $missedExpectedSqlModes = [];

        foreach ($this->expectedSqlModes as $expectedSqlMode) {
            if (!\in_array(\strtolower($expectedSqlMode), $sqlModes, true)) {
                $missedExpectedSqlModes[] = $expectedSqlMode;
            }
        }

        if (\count($missedExpectedSqlModes)) {
            return new Failure(\sprintf(
                'Missed required SQL modes (%s).',
                \implode(', ', $missedExpectedSqlModes)
            ));
        }

        $existExcludedSqlModes = [];

        foreach ($this->excludedSqlModes as $excludedSqlMode) {
            if (\in_array(\strtolower($excludedSqlMode), $sqlModes, true)) {
                $existExcludedSqlModes[] = $excludedSqlMode;
            }
        }

        if (\count($existExcludedSqlModes)) {
            return new Failure(\sprintf(
                'Exist %s sql modes, but those modes must be excluded.',
                \implode(', ', $existExcludedSqlModes)
            ));
        }

        return new Success('All required SQL modes exist.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = parent::getExtraParameters();

        if (null !== $this->actualSqlModes) {
            $parameters['actual sql modes'] = $this->actualSqlModes;
        }

        $parameters['expected sql modes'] = $this->expectedSqlModes;
        $parameters['excluded sql modes'] = $this->excludedSqlModes;

        return $parameters;
    }
}
