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

namespace FiveLab\Component\Diagnostic\Check\ClickHouse;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Dsn;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;

readonly class ClickHouseTableCheck implements CheckInterface
{
    use ClickHouseHelperTrait;

    private HttpAdapterInterface $http;

    public function __construct(private Dsn $dsn, private string $tableName, ?HttpAdapterInterface $http = null)
    {
        $this->http = $http ?? new HttpAdapter();
    }

    public function check(): Result
    {
        $result = $this->sendRequest($this->http, $this->dsn, '?query=SHOW TABLES');

        if ($result instanceof Result) {
            return $result;
        }

        $tables = \explode(PHP_EOL, $result);
        $tables = \array_map(\trim(...), $tables);
        $tables = \array_filter($tables);

        if (\in_array($this->tableName, $tables, true)) {
            return new Success('Successfully checked existence of table in ClickHouse.');
        }

        return new Failure('Table does not exist in ClickHouse.');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn'   => $this->dsn->getDsn(path: true, maskedPassword: true),
            'table' => $this->tableName,
        ];
    }
}
