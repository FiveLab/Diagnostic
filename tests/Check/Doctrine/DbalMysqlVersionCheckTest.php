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

namespace FiveLab\Component\Diagnostic\Tests\Check\Doctrine;

use FiveLab\Component\Diagnostic\Check\Doctrine\DbalMysqlVersionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class DbalMysqlVersionCheckTest extends AbstractDoctrineCheckTestCase
{
    private const RIGHT_MYSQL_VERSION = '~8.0.0';
    private const WRONG_MYSQL_VERSION = '~1.0.0';

    #[Test]
    public function shouldPassCheckForProperVersion(): void
    {
        $check = new DbalMysqlVersionCheck($this->makeDbalConnection(), self::RIGHT_MYSQL_VERSION);
        $result = $check->check();

        self::assertEquals(new Success('MySQL version matches an expected one.'), $result);
    }

    #[Test]
    public function shouldFailCheckForImproperVersion(): void
    {
        $check = new DbalMysqlVersionCheck($this->makeDbalConnection(), self::WRONG_MYSQL_VERSION);
        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString(
            'Expected MySQL server of version',
            $result->message
        );
    }

    #[Test]
    public function shouldUnveilAllProperExtraParametersAfterCheck(): void
    {
        $connection = $this->makeDbalConnection();

        $check = new DbalMysqlVersionCheck($connection, self::RIGHT_MYSQL_VERSION);
        $check->check();

        $actualBuildVersion = $this->getMysqlServerBuildVersion($connection);
        $actualDistribVersion = $this->extractMysqlServerDistribVersion($actualBuildVersion);

        self::assertEquals([
            'host'            => $this->getDatabaseHost(),
            'port'            => $this->getDatabasePort(),
            'user'            => $this->getDatabaseUser(),
            'pass'            => '***',
            'dbname'          => $this->getDatabaseName(),
            'expectedVersion' => self::RIGHT_MYSQL_VERSION,
            'actualVersion'   => $actualDistribVersion,
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldUnveilUnknownActualVersionBeforeSuccessfulCheck(): void
    {
        $check = new DbalMysqlVersionCheck($this->makeDbalConnection(), self::RIGHT_MYSQL_VERSION);
        $parameters = $check->getExtraParameters();

        self::assertArrayHasKey('actualVersion', $parameters);
        self::assertEquals('unknown', $parameters['actualVersion']);
    }

    #[Test]
    public function shouldUnveilUnknownActualVersionOnConnectionError(): void
    {
        $connection = $this->makeDbalConnection([
            'password' => \uniqid(),
        ]);

        $check = new DbalMysqlVersionCheck($connection, self::RIGHT_MYSQL_VERSION);

        $parameters = $check->getExtraParameters();
        self::assertArrayHasKey('actualVersion', $parameters);
        self::assertEquals('unknown', $parameters['actualVersion']);
    }

    #[Test]
    public function shouldUnveilActualVersionOnVersionMismatch(): void
    {
        $connection = $this->makeDbalConnection();

        $check = new DbalMysqlVersionCheck($connection, self::WRONG_MYSQL_VERSION);
        $check->check();

        $actualBuildVersion = $this->getMysqlServerBuildVersion($connection);
        $actualDistribVersion = $this->extractMysqlServerDistribVersion($actualBuildVersion);

        $parameters = $check->getExtraParameters();
        self::assertArrayHasKey('actualVersion', $parameters);
        self::assertEquals($actualDistribVersion, $parameters['actualVersion']);
    }

    #[Test]
    public function shouldFailCheckForWrongCredentials(): void
    {
        $connection = $this->makeDbalConnection([
            'password' => \uniqid(),
        ]);

        $check = new DbalMysqlVersionCheck($connection, self::RIGHT_MYSQL_VERSION);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString(
            'SQLSTATE[HY000] [1045]',
            $result->message
        );
    }

    #[Test]
    public function shouldFailIfHostIsInvalid(): void
    {
        $connection = $this->makeDbalConnection([
            'host' => \uniqid(),
        ]);

        $check = new DbalMysqlVersionCheck($connection, self::RIGHT_MYSQL_VERSION);

        $result = $check->check();

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringContainsString(
            'SQLSTATE[HY000] [2002]',
            $result->message
        );
    }

    #[Test]
    #[TestWith(['5.7.25-0ubuntu0.18.04.2', '5.7.25'])]
    #[TestWith(['5.0.27-standard', '5.0.27'])]
    #[TestWith(['5.0.91-community-nt-log', '5.0.91'])]
    #[TestWith(['10.1.29-MariaDB', '10.1.29'])]
    public function mysqlVersionRegexShouldExtractValidVersions(string $buildVersion, string $expected): void
    {
        $matches = [];
        \preg_match(DbalMysqlVersionCheck::MYSQL_EXTRACT_VERSION_REGEX, $buildVersion, $matches);

        self::assertArrayHasKey(0, $matches);
        self::assertEquals($expected, $matches[0]);
    }

    #[Test]
    #[TestWith(['abc.5.7.25-0ubuntu0.18.04.2'])]
    public function mysqlVersionRegexShouldNotExtractInvalidVersions(string $version): void
    {
        $matches = [];
        \preg_match(DbalMysqlVersionCheck::MYSQL_EXTRACT_VERSION_REGEX, $version, $matches);

        self::assertEmpty($matches);
    }

    private function getMysqlServerBuildVersion(object $connection): string
    {
        $query = 'SHOW VARIABLES WHERE Variable_name = \'version\'';
        $statement = $connection->executeQuery($query);

        [, $version] = $statement->fetchNumeric();

        return $version;
    }

    private function extractMysqlServerDistribVersion(string $buildVersion): string
    {
        $matches = [];
        \preg_match(DbalMysqlVersionCheck::MYSQL_EXTRACT_VERSION_REGEX, $buildVersion, $matches);

        return \rtrim($matches[0], '.');
    }
}
