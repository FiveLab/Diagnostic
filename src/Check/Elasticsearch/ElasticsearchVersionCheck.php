<?php

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
    private $connectionParameters;

    /**
     * @var string
     */
    private $expectedVersion;

    /**
     * @var string
     */
    private $expectedLuceneVersion;

    /**
     * @var VersionComparatorInterface
     */
    private $versionComparator;

    /**
     * @var string
     */
    private $actualVersion;

    /**
     * @var string
     */
    private $actualLuceneVersion;

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters $connectionParameters
     * @param string                            $expectedVersion
     * @param string                            $expectedLuceneVersion
     * @param VersionComparatorInterface        $versionComparator
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
        $params = [
            'host' => $this->connectionParameters->getHost(),
            'port' => $this->connectionParameters->getPort(),
            'ssl'  => $this->connectionParameters->isSsl() ? 'yes' : 'no',
        ];

        if ($this->connectionParameters->getUsername() || $this->connectionParameters->getPassword()) {
            $params['user'] = $this->connectionParameters->getUsername() ?: '(null)';
            $params['pass'] = '***';
        }

        return \array_merge($params, [
            'actual version'          => $this->actualVersion ?: '(null)',
            'expected version'        => $this->expectedVersion ?: '(null)',
            'actual lucene version'   => $this->actualLuceneVersion ?: '(null)',
            'expected lucene version' => $this->expectedLuceneVersion ?: '(null)',
        ]);
    }
}
