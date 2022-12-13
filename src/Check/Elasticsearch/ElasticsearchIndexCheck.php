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
use Elasticsearch\Common\Exceptions\Missing404Exception as ElasticsearchMissing404Exception ;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\ArrayUtils;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;
use OpenSearch\Common\Exceptions\Missing404Exception as OpenSearchMissing404Exception;

/**
 * Check the elasticsearch index.
 */
class ElasticsearchIndexCheck extends AbstractElasticsearchCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $index;

    /**
     * @var array<string, mixed>
     */
    private array $expectedSettings;

    /**
     * @var array<string, mixed>
     */
    private array $actualSettings = [];

    /**
     * Constructor.
     *
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder
     * @param ElasticsearchConnectionParameters                  $connectionParameters
     * @param string                                             $index
     * @param array<string, mixed>                               $expectedSettings
     */
    public function __construct($clientBuilder, ElasticsearchConnectionParameters $connectionParameters, string $index, array $expectedSettings = [])
    {
        parent::__construct($clientBuilder, $connectionParameters);

        $this->index = $index;
        $this->expectedSettings = $expectedSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        try {
            $client = $this->clientBuilder
                ->setHosts([$this->connectionParameters->getDsn()])
                ->build();

            $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to %s: %s.',
                $this->getEngineName(),
                \rtrim($e->getMessage(), '.')
            ));
        }

        try {
            $indexes = $client->indices()->getSettings([
                'index' => $this->index,
            ]);
        } catch (ElasticsearchMissing404Exception|OpenSearchMissing404Exception $e) {
            return new Failure(\sprintf('The index was not found in %s.', $this->getEngineName()));
        } catch (\Throwable $e) {
            return new Failure(\sprintf('Fail connect to %s: %s.', $this->getEngineName(), \rtrim($e->getMessage(), '.')));
        }

        $indexInfo = $indexes[$this->index];

        $this->actualSettings = $indexInfo['settings'];

        if (\count($this->expectedSettings)) {
            foreach ($this->expectedSettings as $settingName => $expectedValue) {
                try {
                    $actualValue = ArrayUtils::tryGetSpecificSettingFromSettings($settingName, $this->actualSettings);
                } catch (\UnexpectedValueException $error) {
                    return new Failure($error->getMessage());
                }

                if ($actualValue !== $expectedValue) {
                    return new Failure(\sprintf(
                        'The setting "%s" is different to expected.',
                        $settingName
                    ));
                }
            }
        }

        return new Success(\sprintf('Success check %s index.', $this->getEngineName()));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = $this->convertConnectionParametersToArray();

        $actualSettings = $this->actualSettings;

        // Remove specific parameters
        unset($actualSettings['index']['routing']);

        return \array_merge($parameters, [
            'index'             => $this->index,
            'expected settings' => \count($this->expectedSettings) ? $this->expectedSettings : '(none)',
            'actual settings'   => $actualSettings,
        ]);
    }
}
