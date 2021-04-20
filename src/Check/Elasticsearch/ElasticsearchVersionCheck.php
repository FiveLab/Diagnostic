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

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;

/**
 * Check elasticsearch and lucene version.
 */
class ElasticsearchVersionCheck implements CheckInterface
{
    /**
     * @var ElasticsearchConnectionParameters
     */
    private ElasticsearchConnectionParameters $connectionParameters;

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
     * @param ElasticsearchConnectionParameters $connectionParameters
     * @param string|null                       $expectedVersion
     * @param string|null                       $expectedLuceneVersion
     * @param VersionComparatorInterface|null   $versionComparator
     */
    public function __construct(ElasticsearchConnectionParameters $connectionParameters, string $expectedVersion = null, string $expectedLuceneVersion = null, VersionComparatorInterface $versionComparator = null)
    {
        $this->connectionParameters = $connectionParameters;
        $this->expectedVersion = $expectedVersion;
        $this->expectedLuceneVersion = $expectedLuceneVersion;
        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(Client::class)) {
            return new Failure('The package "elasticsearch/elasticsearch" is not installed.');
        }

        try {
            $client = ClientBuilder::create()
                ->setHosts([$this->connectionParameters->getDsn()])
                ->build();

            $info = $client->info();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to ElasticSearch: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        if (!\array_key_exists('version', $info)) {
            return new Failure('Missing "version" key in response.');
        }

        $this->actualVersion = $info['version']['number'];
        $this->actualLuceneVersion = $info['version']['lucene_version'];

        if ($this->expectedVersion && !$this->versionComparator->satisfies($this->actualVersion, $this->expectedVersion)) {
            return new Failure('Fail check Elasticsearch version.');
        }

        if ($this->expectedLuceneVersion && !$this->versionComparator->satisfies($this->actualLuceneVersion, $this->expectedLuceneVersion)) {
            return new Failure('Fail check Lucene version.');
        }

        return new Success('Success check Elasticsearch version.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $params = ElasticsearchHelper::convertConnectionParametersToArray($this->connectionParameters);

        return \array_merge($params, [
            'actual version'          => $this->actualVersion ?: '(null)',
            'expected version'        => $this->expectedVersion ?: '(null)',
            'actual lucene version'   => $this->actualLuceneVersion ?: '(null)',
            'expected lucene version' => $this->expectedLuceneVersion ?: '(null)',
        ]);
    }
}
