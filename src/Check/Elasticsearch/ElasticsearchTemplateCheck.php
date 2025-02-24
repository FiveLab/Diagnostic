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
use FiveLab\Component\Diagnostic\Util\ArrayUtils;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;

class ElasticsearchTemplateCheck implements CheckInterface
{
    use ElasticsearchHelperTrait;

    private HttpAdapterInterface $http;

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
     * @param ElasticsearchConnectionParameters $connectionParameters
     * @param string                            $name
     * @param array<string>                     $expectedPatterns
     * @param array<string, mixed>              $expectedSettings
     * @param HttpAdapterInterface|null         $http
     */
    public function __construct(
        private readonly ElasticsearchConnectionParameters $connectionParameters,
        private readonly string                            $name,
        private array                                      $expectedPatterns = [],
        private readonly array                             $expectedSettings = [],
        ?HttpAdapterInterface                              $http = null
    ) {
        $this->http = $http ?? new HttpAdapter();
    }

    public function check(): Result
    {
        $result = $this->sendRequest($this->http, $this->connectionParameters, '_template/'.$this->name);

        if ($result instanceof Result) {
            return $result;
        }

        $templateInfo = $result[$this->name] ?? null;

        if (!$templateInfo) {
            return new Failure(\sprintf('The index template "%s" was not found.', $this->name));
        }

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

        return new Success(\sprintf('Success check "%s" template.', $this->name));
    }

    public function getExtraParameters(): array
    {
        $parameters = [
            'dsn' => $this->connectionParameters->getDsn(true),
        ];

        return \array_merge($parameters, [
            'template'                => $this->name,
            'expected index patterns' => \count($this->expectedPatterns) ? $this->expectedPatterns : '(none)',
            'actual index patterns'   => $this->actualPatterns,
            'expected settings'       => \count($this->expectedSettings) ? $this->expectedSettings : '(none)',
            'actual settings'         => $this->actualSettings,
        ]);
    }
}
