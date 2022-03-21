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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoExtendedConnectionParameters;
use PHPUnit\Framework\TestCase;

abstract class AbstractMongoTestCase extends TestCase
{
    /**
     * @return string|null
     */
    protected function getHost(): ?string
    {
        return \getenv('MONGO_HOST') ?: null;
    }

    /**
     * @return int
     */
    protected function getPort(): int
    {
        return \getenv('MONGO_PORT') ? (int) \getenv('MONGO_PORT') : 27017;
    }

    /**
     * @return bool
     */
    protected function isSsl(): bool
    {
        return (bool) \getenv('MONGO_SSL');
    }

    /**
     * @return string|null
     */
    protected function getUsername(): ?string
    {
        return \getenv('MONGO_USER') ?: null;
    }

    /**
     * @return string|null
     */
    protected function getPassword(): ?string
    {
        return \getenv('MONGO_PASSWORD') ?: null;
    }

    /**
     * @return string
     */
    protected function getDb(): string
    {
        return \getenv('MONGO_DB');
    }

    /**
     * @return string
     */
    protected function getCollection(): string
    {
        return \getenv('MONGO_COLLECTION');
    }

    /**
     * @return bool
     */
    protected function connectionParametersProvided(): bool
    {
        return $this->getHost() && $this->getPort() && $this->getDb();
    }

    /**
     * @return MongoConnectionParameters
     */
    protected function getConnectionParameters(): MongoConnectionParameters
    {
        return new MongoConnectionParameters(
            $this->getHost(),
            $this->getPort(),
            $this->isSsl()
        );
    }

    /**
     * @return MongoExtendedConnectionParameters
     */
    protected function getExtendedConnectionParameters(): MongoExtendedConnectionParameters
    {
        return new MongoExtendedConnectionParameters(
            $this->getUsername(),
            $this->getPassword(),
            $this->getDb(),
            $this->getCollection(),
            $this->getConnectionParameters()
        );
    }

    /**
     * @return string
     */
    protected function getSchema(): string
    {
        return \json_encode(
            [
                "required" => [
                    "a",
                    "b",
                    "c",
                ],
                "properties" => [
                    "a" => [
                        "bsonType" => "string",
                    ],
                    "b" => [
                        "bsonType" => "string",
                    ],
                    "c" => [
                        "bsonType" => "string",
                    ],
                ],
            ]
        );
    }
}
