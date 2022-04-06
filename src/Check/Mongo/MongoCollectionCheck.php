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
     * @var MongoConnectionParameters
     */
    private MongoConnectionParameters $connectionParameters;

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
     * @param MongoConnectionParameters $connectionParameters
     * @param string                    $collection
     * @param array<string, mixed>      $expectedSettings
     */
    public function __construct(MongoConnectionParameters $connectionParameters, string $collection, array $expectedSettings)
    {
        $this->connectionParameters = $connectionParameters;
        $this->collection = $collection;
        $this->expectedSettings = $expectedSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(Manager::class)) {
            return new Failure('MongoDB driver is not installed.');
        }

        $manager = new Manager($this->connectionParameters->getDsn());

        $listCollections = new Command(
            [
                'listCollections' => 1,
                'filter' => [
                    'name' => $this->collection,
                ],
            ],
        );

        try {
            $cursor = $manager->executeCommand($this->connectionParameters->getDb(), $listCollections);
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
                $this->connectionParameters->getDb()
            );

            return new Failure(\sprintf('MongoDB collection check failed: %s', $msg));
        }

        $this->actualSettings = $actualSettings[0];

        if (\count($this->expectedSettings)) {
            foreach ($this->expectedSettings as $settingName => $expectedValue) {
                try {
                    $actualValue = ArrayUtils::tryGetSpecificSettingFromSettings($settingName, $this->actualSettings);
                } catch (\UnexpectedValueException $error) {
                    return new Failure($error->getMessage());
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
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $actualSettings = $this->actualSettings;

        unset($actualSettings['info'], $actualSettings['idIndex']);

        return \array_merge(
            MongoHelper::convertConnectionParametersToArray($this->connectionParameters),
            [
                'collection' => $this->collection,
                'expected settings' => \count($this->expectedSettings) ? $this->expectedSettings : '(none)',
                'actual settings' => $actualSettings,
            ],
        );
    }
}
