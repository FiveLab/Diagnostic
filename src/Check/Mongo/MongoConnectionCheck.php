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
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\Exception;
use MongoDB\Driver\Manager;

readonly class MongoConnectionCheck implements CheckInterface
{
    public function __construct(private MongoConnectionParameters $connectionParameters)
    {
    }

    public function check(): Result
    {
        if (!\class_exists(Manager::class)) {
            return new Failure('MongoDB driver is not installed.');
        }

        $manager = new Manager($this->connectionParameters->getDsn());
        $ping = new Command(['ping' => 1]);

        try {
            $manager->executeCommand($this->connectionParameters->db, $ping);
        } catch (Exception $e) {
            return new Failure(\sprintf(
                'MongoDB connection failed: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return new Success('Successful MongoDB connection.');
    }

    public function getExtraParameters(): array
    {
        return MongoHelper::convertConnectionParametersToArray($this->connectionParameters);
    }
}
