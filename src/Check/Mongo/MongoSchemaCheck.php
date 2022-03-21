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
 * Check MongoDB collection json-schema.
 */
class MongoSchemaCheck implements CheckInterface
{
    /**
     * @param MongoExtendedConnectionParameters $extendedConnectionParameters
     * @param string                            $expectedSchema
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(private MongoExtendedConnectionParameters $extendedConnectionParameters, private string $expectedSchema)
    {
        if (\json_decode($this->expectedSchema) === null) {
            throw new \InvalidArgumentException('invalid json-schema given.');
        }
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
        $listCollections = new Command(['listCollections' => 1]);

        $connectionCheckResult = $this->checkConnection();

        if ($connectionCheckResult instanceof Failure) {
            return $connectionCheckResult;
        }

        try {
            $cursor = $manager->executeCommand($this->extendedConnectionParameters->getDb(), $listCollections);
        } catch (Exception $e) {
            return new Failure(\sprintf(
                'MongoDB collection schema check failed: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        $actualSchema = \json_encode($cursor->toArray()[0]->options->validator->{'$jsonSchema'});

        if (\json_decode($actualSchema, true) !== \json_decode($this->expectedSchema, true)) {
            return new Failure('MongoDB collection schema check failed: expected json-schema does not equal the actual one.');
        }

        return new Success('Successful MongoDB collection schema check.');
    }

    /**
     * @return array
     */
    public function getExtraParameters(): array
    {
        return MongoHelper::convertExtendedConnectionParametersToArray($this->extendedConnectionParameters);
    }

    /**
     * @return ResultInterface
     */
    private function checkConnection(): ResultInterface
    {
        $connectionCheck = new MongoConnectionCheck(
            new MongoConnectionParameters(
                $this->extendedConnectionParameters->connectionParameters->getHost(),
                $this->extendedConnectionParameters->connectionParameters->getPort(),
                $this->extendedConnectionParameters->connectionParameters->isSsl()
            )
        );

        return $connectionCheck->check();
    }
}
