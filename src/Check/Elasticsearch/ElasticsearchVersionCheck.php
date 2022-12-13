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

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

/**
 * Check elasticsearch and lucene version.
 */
class ElasticsearchVersionCheck extends AbstractElasticsearchCheck implements CheckInterface
{
    /**
     * @var string|null
     */
    private ?string $expectedVersion;

    /**
     * @var string|null
     */
    private ?string $expectedLuceneVersion;

    /**
     * @var VersionComparatorInterface
     */
    private VersionComparatorInterface $versionComparator;

    /**
     * @var string|null
     */
    private ?string $actualVersion = null;

    /**
     * @var string|null
     */
    private ?string $actualLuceneVersion = null;

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters                       $connectionParameters
     * @param string|null                                             $expectedVersion
     * @param string|null                                             $expectedLuceneVersion
     * @param VersionComparatorInterface|null                         $versionComparator
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder|null $clientBuilder
     */
    public function __construct(ElasticsearchConnectionParameters $connectionParameters, string $expectedVersion = null, string $expectedLuceneVersion = null, VersionComparatorInterface $versionComparator = null, $clientBuilder = null)
    {
        parent::__construct($connectionParameters, $clientBuilder);

        $this->expectedVersion = $expectedVersion;
        $this->expectedLuceneVersion = $expectedLuceneVersion;
        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        try {
            /** @var ElasticsearchClient|OpenSearchClient */
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

        if ($this->expectedVersion && !$this->versionComparator->satisfies($this->actualVersion, $this->expectedVersion)) {
            return new Failure(\sprintf('Fail check %s version.', $this->getEngineName()));
        }

        if ($this->expectedLuceneVersion && !$this->versionComparator->satisfies($this->actualLuceneVersion, $this->expectedLuceneVersion)) {
            return new Failure('Fail check Lucene version.');
        }

        return new Success(\sprintf('Success check %s version.', $this->getEngineName()));
    }

    /**
     * {@inheritdoc}
     */
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
