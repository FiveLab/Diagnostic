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

namespace FiveLab\Component\Diagnostic\Check\Mongo;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Command;
use FiveLab\Component\Diagnostic\Util\ArrayUtils;

/**
 * Check MongoDB collection json-schema.
 */
class MongoCollectionCheck implements CheckInterface
{
    /**
     * @var MongoExtendedConnectionParameters
     */
    private MongoExtendedConnectionParameters $extendedConnectionParameters;

    /**
     * @var string
     */
    private string $collection;

    /**
     * @var array<string,mixed>
     */
    private array $expectedSettings;

    /**
     * @var array<string,mixed>
     */
    private array $actualSettings = [];

    /**
     * @param MongoExtendedConnectionParameters $extendedConnectionParameters
     * @param string                            $collection
     * @param array<string, mixed>              $expectedSettings
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(MongoExtendedConnectionParameters $extendedConnectionParameters, string $collection, array $expectedSettings)
    {
        $this->extendedConnectionParameters = $extendedConnectionParameters;
        $this->collection = $collection;
        $this->expectedSettings = $expectedSettings;
    }

    /**
     * @return ResultInterface
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(Manager::class)) {
            return new Failure('MongoDB driver is not installed.');
        }

        $manager = new Manager($this->extendedConnectionParameters->getDsn());
        $listCollections = new Command(
            [
                'listCollections' => 1,
                'filter' => [
                    'name' => $this->collection,
                ],
            ],
        );

        $connectionCheckResult = $this->checkConnection($manager);

        if ($connectionCheckResult instanceof Failure) {
            return $connectionCheckResult;
        }

        try {
            $cursor = $manager->executeCommand($this->extendedConnectionParameters->getDb(), $listCollections);
            $cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
        } catch (Exception $e) {
            return new Failure(\sprintf(
                'MongoDB collection check failed: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        $actualSettings = $cursor->toArray();

        if (!\count($actualSettings)) {
            $msg = \sprintf(
                'collection \'%s\' not found in db \'%s\'.',
                $this->collection,
                $this->extendedConnectionParameters->getDb()
            );

            return new Failure(\sprintf('MongoDB collection check failed: %s', $msg));
        }

        $this->actualSettings = $actualSettings[0];

        if (\count($this->expectedSettings)) {
            foreach ($this->expectedSettings as $settingName => $expectedValue) {
                $actualValue = ArrayUtils::tryGetSpecificSettingFromSettings($settingName, $this->actualSettings);

                if ($actualValue instanceof Failure) {
                    return $actualValue;
                }

                if ($actualValue !== $expectedValue) {
                    return new Failure(\sprintf(
                        'MongoDB collection check failed: the actual setting \'%s\' is different than expected.',
                        $settingName
                    ));
                }
            }
        }

        return new Success('Successful MongoDB collection check.');
    }

    /**
     * @return array
     */
    public function getExtraParameters(): array
    {
        $actualSettings = $this->actualSettings;

        unset($actualSettings['info']);
        unset($actualSettings['idIndex']);

        return \array_merge(
            MongoHelper::convertExtendedConnectionParametersToArray($this->extendedConnectionParameters),
            [
                'collection' => $this->collection,
                'expected settings' => \count($this->expectedSettings) ? $this->expectedSettings : '(none)',
                'actual settings' => $actualSettings,
            ],
        );
    }

    /**
     * @param Manager $manager
     *
     * @return ResultInterface
     */
    private function checkConnection(Manager $manager): ResultInterface
    {
        $ping = new Command(['ping' => 1]);

        try {
            $manager->executeCommand('test', $ping);
        } catch (Exception $e) {
            return new Failure(\sprintf(
                'MongoDB connection failed: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return new Success('Successful MongoDB connection.');
    }
}
