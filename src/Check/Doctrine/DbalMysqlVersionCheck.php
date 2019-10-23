<?php

namespace FiveLab\Component\Diagnostic\Check\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;

/**
 * Check MySQL version.
 */
class DbalMysqlVersionCheck implements CheckInterface
{
    const MYSQL_EXTRACT_VERSION_REGEX = '/^([\d\.]+)/';

    /**
     * @var DriverConnection
     */
    private $connection;

    /**
     * @var string
     */
    private $expectedVersion;

    /**
     * @var VersionComparatorInterface
     */
    private $versionComparator;

    /**
     * @var string
     */
    private $actualVersion = 'unknown';

    /**
     * Constructor.
     *
     * @param DriverConnection           $connection
     * @param string                     $expectedVersion   Expected MySQL version in composer format
     * @param VersionComparatorInterface $versionComparator
     *
     * @see https://getcomposer.org/doc/articles/versions.md
     */
    public function __construct(DriverConnection $connection, string $expectedVersion, VersionComparatorInterface $versionComparator = null)
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
            $statement = $this->connection->executeQuery($query);
            $mysqlVersionVariableContent = $statement->fetchColumn(1);
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Failed checking MySQL version: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }
        $this->actualVersion = $this->extractMysqlServerDistribVersion($mysqlVersionVariableContent);
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

        if ($this->connection instanceof Connection) {
            $parameters = [
                'host'            => $this->connection->getHost(),
                'port'            => $this->connection->getPort(),
                'user'            => $this->connection->getUsername(),
                'pass'            => '***',
                'dbname'          => $this->connection->getDatabase(),
            ];
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
    private function extractMysqlServerDistribVersion(string $buildVersion): string
    {
        $matches = [];
        preg_match(self::MYSQL_EXTRACT_VERSION_REGEX, $buildVersion, $matches);

        return \rtrim($matches[0], '.');
    }
}
