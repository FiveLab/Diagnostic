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
use Elasticsearch\Common\Exceptions\Missing404Exception as ElasticsearchMissing404Exception;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\ArrayUtils;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;
use OpenSearch\Common\Exceptions\Missing404Exception as OpenSearchMissing404Exception;

/**
 * Check the elasticsearch template.
 */
class ElasticsearchTemplateCheck extends AbstractElasticsearchCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var array<string>
     */
    private array $expectedPatterns;

    /**
     * @var array<string, mixed>
     */
    private array $expectedSettings;

    /**
     * @var array<string>
     */
    private array $actualPatterns = [];

    /**
     * @var array<string, mixed>
     */
    private array $actualSettings = [];

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters                       $connectionParameters
     * @param string                                                  $name
     * @param array<string>                                           $expectedPatterns
     * @param array<string, mixed>                                    $expectedSettings
     * @param ElasticsearchClientBuilder|OpenSearchClientBuilder|null $clientBuilder
     */
    public function __construct(ElasticsearchConnectionParameters $connectionParameters, string $name, array $expectedPatterns = [], array $expectedSettings = [], $clientBuilder = null)
    {
        parent::__construct($connectionParameters, $clientBuilder);

        $this->name = $name;
        $this->expectedPatterns = $expectedPatterns;
        $this->expectedSettings = $expectedSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        try {
            /** @var ElasticsearchClient|OpenSearchClient */
            $client = $this->createClient();

            $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to %s: %s.',
                $this->getEngineName(),
                \rtrim($e->getMessage(), '.')
            ));
        }

        try {
            $template = $client->indices()->getTemplate([
                'name' => $this->name,
            ]);
        } catch (ElasticsearchMissing404Exception|OpenSearchMissing404Exception $e) {
            return new Failure(\sprintf('The template was not found in %s.', $this->getEngineName()));
        } catch (\Throwable $e) {
            return new Failure(\sprintf('Fail connect to %s: %s.', $this->getEngineName(), \rtrim($e->getMessage(), '.')));
        }

        $templateInfo = $template[$this->name];

        $this->actualPatterns = $templateInfo['index_patterns'];
        $this->actualSettings = $templateInfo['settings'];

        if (\count($this->expectedPatterns)) {
            \sort($this->expectedPatterns);
            \sort($this->actualPatterns);

            if ($this->expectedPatterns !== $this->actualPatterns) {
                return new Failure('Fail check index patterns.');
            }
        }

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

        return new Success('Success check Elasticsearch template.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = $this->convertConnectionParametersToArray();

        return \array_merge($parameters, [
            'template'                => $this->name,
            'expected index patterns' => \count($this->expectedPatterns) ? $this->expectedPatterns : '(none)',
            'actual index patterns'   => $this->actualPatterns,
            'expected settings'       => \count($this->expectedSettings) ? $this->expectedSettings : '(none)',
            'actual settings'         => $this->actualSettings,
        ]);
    }
}
