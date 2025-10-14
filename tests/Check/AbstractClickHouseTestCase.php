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
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use PHPUnit\Framework\TestCase;

abstract class AbstractClickHouseTestCase extends TestCase
{
    protected function canTestWithClickHouse(): bool
    {
        return (bool) $this->getClickHouseHost();
    }

    protected function getClickHouseDsn(): Dsn
    {
        return new Dsn(
            'http',
            $this->getClickHouseHost(),
            '/'.$this->getClickHouseDb(),
            $this->getClickHousePort(),
            $this->getClickHouseUser(),
            $this->getClickHousePassword()
        );
    }

    protected function getClickHouseHost(): ?string
    {
        return \getenv('CLICKHOUSE_HOST');
    }

    protected function getClickHousePort(): ?int
    {
        return \getenv('CLICKHOUSE_PORT') ? (int) \getenv('CLICKHOUSE_PORT') : 8123;
    }

    protected function getClickHouseUser(): ?string
    {
        return \getenv('CLICKHOUSE_USER') ?: 'diagnostic';
    }

    protected function getClickHousePassword(): ?string
    {
        return \getenv('CLICKHOUSE_PASSWORD') ?: 'Q1w2e3r4';
    }

    protected function getClickHouseDb(): ?string
    {
        return \getenv('CLICKHOUSE_DB') ?: 'diagnostic';
    }

    protected function dropAllTables(): void
    {
        $tables = $this->sendQuery('GET', 'SHOW TABLES');
        $tableNames = \explode(PHP_EOL, $tables);
        $tableNames = \array_map(\trim(...), $tableNames);
        $tableNames = \array_filter($tableNames);

        foreach ($tableNames as $tableName) {
            $this->sendQuery('POST', 'DROP TABLE '.$tableName);
        }
    }

    protected function sendQuery(string $method, string $query): string
    {
        $adapter = new HttpAdapter();
        $dsn = $this->getClickHouseDsn();

        if ('GET' === $method) {
            $uri = \sprintf(
                '%s://%s:%d?query=%s',
                $dsn->scheme,
                $dsn->host,
                $dsn->port,
                $query
            );

            $body = null;
        } elseif ('POST' === $method) {
            $uri = \sprintf(
                '%s://%s:%d',
                $dsn->scheme,
                $dsn->host,
                $dsn->port
            );

            $body = $query;
        } else {
            throw new \InvalidArgumentException(\sprintf('Unknown method "%s".', $method));
        }

        $request = $adapter->createRequest($method, $uri, [
            'authorization'         => 'Basic '.\base64_encode(\sprintf('%s:%s', $dsn->username, $dsn->password)),
            'x-clickhouse-database' => $dsn->target,
        ], $body);

        $response = $adapter->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(\sprintf(
                'Receive wrong status code "%d" (%s).',
                $response->getStatusCode(),
                $response->getBody()
            ));
        }

        return \trim((string) $response->getBody());
    }
}
