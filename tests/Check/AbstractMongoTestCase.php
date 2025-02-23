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
use PHPUnit\Framework\TestCase;

abstract class AbstractMongoTestCase extends TestCase
{
    protected function getProtocol(): ?string
    {
        return \getenv('MONGO_PROTOCOL') ?: null;
    }

    protected function getHost(): ?string
    {
        return \getenv('MONGO_HOST') ?: null;
    }

    protected function getPort(): int
    {
        return \getenv('MONGO_PORT') ? (int) \getenv('MONGO_PORT') : 27017;
    }

    protected function getUsername(): ?string
    {
        return \getenv('MONGO_USER') ?: null;
    }

    protected function getPassword(): ?string
    {
        return \getenv('MONGO_PASSWORD') ?: null;
    }

    protected function getDb(): string
    {
        return \getenv('MONGO_DB');
    }

    protected function getCollection(): string
    {
        return \getenv('MONGO_COLLECTION');
    }

    protected function connectionParametersProvided(): bool
    {
        return $this->getHost() && $this->getPort() && $this->getDb();
    }

    protected function getConnectionParameters(): MongoConnectionParameters
    {
        return new MongoConnectionParameters(
            $this->getProtocol(),
            $this->getHost(),
            $this->getPort(),
            $this->getUsername(),
            $this->getPassword(),
            $this->getDb(),
        );
    }

    protected function getExpectedSettings(): array
    {
        return [
            'options.validator.$jsonSchema' => [
                'required' => [
                    'a',
                    'b',
                    'c',
                ],
                'properties' => [
                    'a' => [
                        'bsonType' => 'string',
                    ],
                    'b' => [
                        'bsonType' => 'string',
                    ],
                    'c' => [
                        'bsonType' => 'string',
                    ],
                ],
            ],
        ];
    }
}
