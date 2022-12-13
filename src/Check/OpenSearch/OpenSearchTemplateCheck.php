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

namespace FiveLab\Component\Diagnostic\Check\OpenSearch;

use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use OpenSearch\Common\Exceptions\Missing404Exception;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\ArrayUtils;

/**
 * Check the OpenSearch template.
 */
class OpenSearchTemplateCheck implements CheckInterface
{
    /**
     * @var OpenSearchConnectionParameters
     */
    private OpenSearchConnectionParameters $connectionParameters;

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
     * @param OpenSearchConnectionParameters $connectionParams
     * @param string                         $name
     * @param array<string>                  $expectedPatterns
     * @param array<string, mixed>           $expectedSettings
     */
    public function __construct(OpenSearchConnectionParameters $connectionParams, string $name, array $expectedPatterns = [], array $expectedSettings = [])
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
            return new Failure('The package "opensearch-project/opensearch-php" is not installed.');
        }

        try {
            $client = ClientBuilder::create()
                ->setHosts([$this->connectionParameters->getDsn()])
                ->build();

            $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to OpenSearch: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        try {
            $template = $client->indices()->getTemplate([
                'name' => $this->name,
            ]);
        } catch (Missing404Exception $e) {
            return new Failure('The template was not found in OpenSearch.');
        } catch (\Throwable $e) {
            return new Failure(\sprintf('Fail connect to OpenSearch: %s.', \rtrim($e->getMessage(), '.')));
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

        return new Success('Success check OpenSearch template.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = OpenSearchHelper::convertConnectionParametersToArray($this->connectionParameters);

        return \array_merge($parameters, [
            'template'                => $this->name,
            'expected index patterns' => \count($this->expectedPatterns) ? $this->expectedPatterns : '(none)',
            'actual index patterns'   => $this->actualPatterns,
            'expected settings'       => \count($this->expectedSettings) ? $this->expectedSettings : '(none)',
            'actual settings'         => $this->actualSettings,
        ]);
    }
}
