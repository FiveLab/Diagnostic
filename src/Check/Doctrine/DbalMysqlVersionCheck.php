<?php

declare(strict_types = 1);

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FiveLab\Component\Diagnostic\Check\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;

class DbalMysqlVersionCheck extends AbstractDbalCheck
{
    public const MYSQL_EXTRACT_VERSION_REGEX = '/^([\d\.]+)/';

    private readonly VersionComparatorInterface $versionComparator;
    private string $actualVersion = 'unknown';

    public function __construct(DriverConnection|Connection $connection, private readonly string $expectedVersion, ?VersionComparatorInterface $versionComparator = null)
    {
        parent::__construct($connection);

        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
    }

    public function check(): Result
    {
        try {
            $query = 'SHOW VARIABLES WHERE Variable_name = \'version\'';
            $statement = $this->connection->executeQuery($query);

            [, $mysqlVersionVariableContent] = $statement->fetchNumeric(); // @phpstan-ignore-line
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Failed checking MySQL version: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        $this->actualVersion = $this->extractMysqlServerDistributedVersion($mysqlVersionVariableContent);

        if (!$this->versionComparator->satisfies($this->actualVersion, $this->expectedVersion)) {
            return new Failure(\sprintf(
                'Expected MySQL server of version "%s", found "%s".',
                $this->expectedVersion,
                $this->actualVersion
            ));
        }

        return new Success('MySQL version matches an expected one.');
    }

    public function getExtraParameters(): array
    {
        $parameters = parent::getExtraParameters();

        $parameters['actualVersion'] = $this->actualVersion;
        $parameters['expectedVersion'] = $this->expectedVersion;

        return $parameters;
    }

    private function extractMysqlServerDistributedVersion(string $buildVersion): string
    {
        $matches = [];
        \preg_match(self::MYSQL_EXTRACT_VERSION_REGEX, $buildVersion, $matches);

        return \rtrim($matches[0], '.'); // @phpstan-ignore-line
    }
}
