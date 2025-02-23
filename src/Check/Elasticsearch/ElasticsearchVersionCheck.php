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

use Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

class ElasticsearchVersionCheck extends AbstractElasticsearchCheck implements CheckInterface
{
    private readonly VersionComparatorInterface $versionComparator;
    private ?string $actualVersion = null;
    private ?string $actualLuceneVersion = null;

    public function __construct(
        ElasticsearchConnectionParameters                       $connectionParameters,
        private readonly ?string                                $expectedVersion = null,
        private readonly ?string                                $expectedLuceneVersion = null,
        ?VersionComparatorInterface                             $versionComparator = null,
        ElasticsearchClientBuilder|OpenSearchClientBuilder|null $clientBuilder = null
    ) {
        parent::__construct($connectionParameters, $clientBuilder);

        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
    }

    public function check(): Result
    {
        try {
            $client = $this->createClient();

            $info = $client->info();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to %s: %s.',
                $this->getEngineName(),
                \rtrim($e->getMessage(), '.')
            ));
        }

        if (!\array_key_exists('version', $info)) {
            return new Failure('Missing "version" key in response.');
        }

        $this->actualVersion = $info['version']['number'];
        $this->actualLuceneVersion = $info['version']['lucene_version'];

        if ($this->expectedVersion && !$this->versionComparator->satisfies((string) $this->actualVersion, $this->expectedVersion)) {
            return new Failure(\sprintf('Fail check %s version.', $this->getEngineName()));
        }

        if ($this->expectedLuceneVersion && !$this->versionComparator->satisfies((string) $this->actualLuceneVersion, $this->expectedLuceneVersion)) {
            return new Failure('Fail check Lucene version.');
        }

        return new Success(\sprintf('Success check %s version.', $this->getEngineName()));
    }

    public function getExtraParameters(): array
    {
        $params = $this->convertConnectionParametersToArray();

        return \array_merge($params, [
            'actual version'          => $this->actualVersion ?: '(null)',
            'expected version'        => $this->expectedVersion ?: '(null)',
            'actual lucene version'   => $this->actualLuceneVersion ?: '(null)',
            'expected lucene version' => $this->expectedLuceneVersion ?: '(null)',
        ]);
    }
}
