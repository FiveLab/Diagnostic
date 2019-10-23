<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check the elasticsearch template.
 */
class ElasticsearchTemplateCheck implements CheckInterface
{
    /**
     * @var ElasticsearchConnectionParameters
     */
    private $connectionParameters;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $expectedPatterns;

    /**
     * @var array
     */
    private $expectedSettings = [];

    /**
     * @var array
     */
    private $actualPatterns = [];

    /**
     * @var array
     */
    private $actualSettings = [];

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters $connectionParams
     * @param string                            $name
     * @param array|null                        $expectedPatterns
     * @param array|null                        $expectedSettings
     */
    public function __construct(ElasticsearchConnectionParameters $connectionParams, string $name, array $expectedPatterns = [], array $expectedSettings = [])
    {
        $this->connectionParameters = $connectionParams;
        $this->name = $name;
        $this->expectedPatterns = $expectedPatterns;
        $this->expectedSettings = $expectedSettings;
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

            $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to ElasticSearch: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        try {
            $template = $client->indices()->getTemplate([
                'name' => $this->name,
            ]);
        } catch (Missing404Exception $e) {
            return new Failure('The template was not found in Elasticsearch.');
        } catch (\Throwable $e) {
            return new Failure(\sprintf('Fail connect to ElasticSearch: %s.', \rtrim($e->getMessage(), '.')));
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
                $actualValue = $this->tryGetSettingFromTemplateSettings($settingName, $this->actualSettings);

                if ($actualValue instanceof Failure) {
                    return $actualValue;
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
        $parameters = [
            'host' => $this->connectionParameters->getHost(),
            'port' => $this->connectionParameters->getPort(),
            'ssl'  => $this->connectionParameters->isSsl() ? 'yes' : 'no',
        ];

        if ($this->connectionParameters->getUsername() || $this->connectionParameters->getPassword()) {
            $parameters['user'] = $this->connectionParameters->getUsername() ?: '(null)';
            $parameters['pass'] = '***';
        }

        return \array_merge($parameters, [
            'template'                => $this->name,
            'expected index patterns' => \count($this->expectedPatterns) ? $this->expectedPatterns : '(none)',
            'actual index patterns'   => $this->actualPatterns,
            'expected settings'       => \count($this->expectedSettings) ? $this->expectedSettings : '(none)',
            'actual settings'         => $this->actualSettings,
        ]);
    }

    /**
     * Try to get the setting from array
     *
     * @param string $path
     * @param array  $settings
     *
     * @return Failure|mixed
     */
    private function tryGetSettingFromTemplateSettings(string $path, array $settings)
    {
        $pathParts = \explode('.', $path);

        $processedPath = '';

        while ($pathPart = \array_shift($pathParts)) {
            $processedPath .= $pathPart.'.';

            if (!\array_key_exists($pathPart, $settings)) {
                return new Failure(\sprintf(
                    'The setting "%s" is missed.',
                    \rtrim($processedPath, '.')
                ));
            }

            if (\count($pathParts)) {
                // Not last element. Get inner array.
                $settings = $settings[$pathPart];
            } else {
                // Last element. Get value.
                return $settings[$pathPart];
            }
        }

        return new Failure(\sprintf('Cannot get setting by path: "%s".', $path));
    }
}
