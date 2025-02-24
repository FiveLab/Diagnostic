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
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;

class ElasticsearchVersionCheck implements CheckInterface
{
    use ElasticsearchHelperTrait;

    private readonly HttpAdapterInterface $http;
    private readonly VersionComparatorInterface $versionComparator;
    private ?string $actualVersion = null;
    private ?string $actualLuceneVersion = null;

    public function __construct(
        private readonly ElasticsearchConnectionParameters $connectionParameters,
        private readonly ?string                           $expectedVersion = null,
        private readonly ?string                           $expectedLuceneVersion = null,
        ?VersionComparatorInterface                        $versionComparator = null,
        ?HttpAdapterInterface                              $http = null
    ) {
        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
        $this->http = $http ?? new HttpAdapter();
    }

    public function check(): Result
    {
        $result = $this->sendRequest($this->http, $this->connectionParameters, '');

        if ($result instanceof Result) {
            return $result;
        }

        $version = $result['version'] ?? null;

        if (!$version) {
            return new Failure('Missing "version" key in response.');
        }

        $this->actualVersion = $version['number'];
        $this->actualLuceneVersion = $version['lucene_version'];

        if ($this->expectedVersion && !$this->versionComparator->satisfies((string) $this->actualVersion, $this->expectedVersion)) {
            return new Failure('Fail check Elasticsearch/Opensearch version.');
        }

        if ($this->expectedLuceneVersion && !$this->versionComparator->satisfies((string) $this->actualLuceneVersion, $this->expectedLuceneVersion)) {
            return new Failure('Fail check Lucene version.');
        }

        return new Success('Success check Elasticsearch/Opensearch version.');
    }

    public function getExtraParameters(): array
    {
        $params = [
            'dsn' => $this->connectionParameters->getDsn(true),
        ];

        return \array_merge($params, [
            'actual version'          => $this->actualVersion ?: '(null)',
            'expected version'        => $this->expectedVersion ?: '(null)',
            'actual lucene version'   => $this->actualLuceneVersion ?: '(null)',
            'expected lucene version' => $this->expectedLuceneVersion ?: '(null)',
        ]);
    }
}
