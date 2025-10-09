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

readonly class ClickHouseDbCheck implements CheckInterface
{
    use ClickHouseHelperTrait;

    private HttpAdapterInterface $http;

    public function __construct(private Dsn $dsn, ?HttpAdapterInterface $http = null)
    {
        $this->http = $http ?? new HttpAdapter();
    }

    public function check(): Result
    {
        $result = $this->sendRequest($this->http, $this->dsn, \sprintf(
            '?query=EXISTS DATABASE %s',
            $this->dsn->target
        ));

        if ($result instanceof Result) {
            return $result;
        }

        if ($result !== '1') {
            return new Failure('Database does not exist in ClickHouse.');
        }

        return new Success('Successfully checked existence of database in ClickHouse.');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn' => $this->dsn->getDsn(path: true, maskedPassword: true),
        ];
    }
}
