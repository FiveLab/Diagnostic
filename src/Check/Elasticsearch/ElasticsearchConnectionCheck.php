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

namespace FiveLab\Component\Diagnostic\Check\Elasticsearch;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;

readonly class ElasticsearchConnectionCheck implements CheckInterface
{
    use ElasticsearchHelperTrait;

    private HttpAdapterInterface $http;

    public function __construct(private ElasticsearchConnectionParameters $connectionParameters, ?HttpAdapterInterface $http = null)
    {
        $this->http = $http ?? new HttpAdapter();
    }

    public function check(): Result
    {
        $result = $this->sendRequest($this->http, $this->connectionParameters, '_cat/health');

        if ($result instanceof Result) {
            return $result;
        }

        $status = $result[0]['status'] ?? null;

        if (!$status) {
            return new Failure('Fail connect to Elasticsearch/Opensearch - missed status in _cat/health.');
        }

        return new Success('Success connect to Elasticsearch/Opensearch.');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn' => $this->connectionParameters->getDsn(true),
        ];
    }
}
