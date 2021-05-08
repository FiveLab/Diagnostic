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

namespace FiveLab\Component\Diagnostic\Check\Eloquent;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;
use Illuminate\Database\ConnectionInterface;

/**
 * Check MySQL version.
 */
class DatabaseMysqlVersionCheck implements CheckInterface
{
    public const MYSQL_EXTRACT_VERSION_REGEX = '/^([\d\.]+)/';

    /**
     * @var ConnectionInterface
     */
    private ConnectionInterface $connection;

    /**
     * @var string
     */
    private string $expectedVersion;

    /**
     * @var VersionComparatorInterface
     */
    private VersionComparatorInterface $versionComparator;

    /**
     * @var string
     */
    private string $actualVersion = 'unknown';

    /**
     * Constructor.
     *
     * @param ConnectionInterface             $connection
     * @param string                          $expectedVersion
     * @param VersionComparatorInterface|null $versionComparator
     */
    public function __construct(ConnectionInterface $connection, string $expectedVersion, VersionComparatorInterface $versionComparator = null)
    {
        $this->connection = $connection;
        $this->expectedVersion = $expectedVersion;
        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        try {
            $query = "SHOW VARIABLES WHERE Variable_name = 'version'";
            $result = $this->connection->select((string) $this->connection->raw($query));
            $mysqlVersionVariableContent = $result[0]->Value;
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

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = [];

        if ($this->connection instanceof ConnectionInterface) {
            $parameters = $this->connection->getConfig();
            $parameters['password'] = '***';
        }

        $parameters['actualVersion'] = $this->actualVersion;
        $parameters['expectedVersion'] = $this->expectedVersion;

        return $parameters;
    }

    /**
     * @param string $buildVersion
     *
     * @return string
     */
    private function extractMysqlServerDistributedVersion(string $buildVersion): string
    {
        $matches = [];
        \preg_match(self::MYSQL_EXTRACT_VERSION_REGEX, $buildVersion, $matches);

        return \rtrim($matches[0], '.');
    }
}
