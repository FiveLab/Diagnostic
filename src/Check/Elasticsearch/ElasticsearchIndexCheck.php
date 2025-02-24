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

class ElasticsearchIndexCheck implements CheckInterface
{
    use ElasticsearchHelperTrait;

    /**
     * @var array<string, mixed>
     */
    private array $actualSettings = [];

    private HttpAdapterInterface $http;

    /**
     * Constructor.
     *
     * @param ElasticsearchConnectionParameters $connectionParameters
     * @param string                            $index
     * @param array<string, mixed>              $expectedSettings
     * @param HttpAdapterInterface|null         $http
     */
    public function __construct(
        private readonly ElasticsearchConnectionParameters $connectionParameters,
        private readonly string                            $index,
        private readonly array                             $expectedSettings = [],
        ?HttpAdapterInterface                              $http = null
    ) {
        $this->http = $http ?? new HttpAdapter();
    }

    public function check(): Result
    {
        $result = $this->sendRequest($this->http, $this->connectionParameters, $this->index.'/_settings');

        if ($result instanceof Result) {
            return $result;
        }

        $indexInfo = $result[$this->index];

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

        return new Success(\sprintf('Success check "%s" index.', $this->index));
    }

    public function getExtraParameters(): array
    {
        $parameters = [
            'dsn' => $this->connectionParameters->getDsn(true),
        ];

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
