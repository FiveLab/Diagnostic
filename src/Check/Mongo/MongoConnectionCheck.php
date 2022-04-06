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

/**
 * Check MongoDB connection.
 */
class MongoConnectionCheck implements CheckInterface
{
    /**
     * @var MongoConnectionParameters
     */
    private MongoConnectionParameters $connectionParameters;

    /**
     * @param MongoConnectionParameters $connectionParameters
     */
    public function __construct(MongoConnectionParameters $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;
    }

    /**
     * @return ResultInterface
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(Manager::class)) {
            return new Failure('MongoDB driver is not installed.');
        }

        $manager = new Manager($this->connectionParameters->getDsn());
        $ping = new Command(['ping' => 1]);

        try {
            $manager->executeCommand($this->connectionParameters->getDb(), $ping);
        } catch (Exception $e) {
            return new Failure(\sprintf(
                'MongoDB connection failed: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return new Success('Successful MongoDB connection.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return MongoHelper::convertConnectionParametersToArray($this->connectionParameters);
    }
}
