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

namespace FiveLab\Component\Diagnostic\Tests\Check\Mongo;

use FiveLab\Component\Diagnostic\Check\Mongo\MongoCollectionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMongoTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\Attributes\Test;

class MongoCollectionCheckTest extends AbstractMongoTestCase
{
    private ?Manager $mongoManager = null;

    protected function setUp(): void
    {
        if (!$this->connectionParametersProvided()) {
            self::markTestSkipped('MongoDB is not configured.');
        }

        $jsonSchema = [
            'required'   => ['a', 'b', 'c'],
            'properties' => [
                'a' => ['bsonType' => 'string'],
                'b' => ['bsonType' => 'string'],
                'c' => ['bsonType' => 'string'],
            ],
        ];

        $createCollection = new Command(
            [
                'create'           => $this->getCollection(),
                'validator'        => [
                    '$jsonSchema' => $jsonSchema,
                ],
                'validationLevel'  => 'strict',
                'validationAction' => 'error',
            ],
        );

        try {
            $this->mongoManager = new Manager($this->getDsn()->getDsn());
            $this->mongoManager->executeCommand($this->getDb(), $createCollection);
        } catch (\Exception $e) {
            self::throwException($e);
        }
    }

    public function tearDown(): void
    {
        try {
            $this->mongoManager?->executeCommand($this->getDb(), new Command(['drop' => $this->getCollection()]));
        } catch (\Exception $e) {
            self::throwException($e);
        }
    }

    #[Test]
    public function shouldSuccessfulCheck(): void
    {
        $check = new MongoCollectionCheck(
            $this->getDsn(),
            $this->getCollection(),
            $this->getExpectedSettings()
        );

        $result = $check->check();

        self::assertEquals(new Success('Successful MongoDB collection check.'), $result);
    }

    #[Test]
    public function shouldFailedCheckConnectionFailed(): void
    {
        $invalidHost = $this->getHost().'_some';

        $dsn = new Dsn(
            $this->getProtocol(),
            $invalidHost,
            '/'.$this->getDb(),
            $this->getPort(),
            $this->getUsername(),
            $this->getPassword()
        );

        $check = new MongoCollectionCheck(
            $dsn,
            $this->getCollection(),
            $this->getExpectedSettings()
        );

        $result = $check->check();

        $msg = \sprintf('MongoDB collection check failed: No suitable servers found (`serverSelectionTryOnce` set): [Failed to resolve \'%s\'].', $invalidHost);

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith($msg, $result->message);
    }

    #[Test]
    public function shouldFailedCheckCollectionNotFound(): void
    {
        $wrongCollection = 'wrong_collection';

        $check = new MongoCollectionCheck(
            $this->getDsn(),
            $wrongCollection,
            $this->getExpectedSettings()
        );

        $result = $check->check();

        $msg = \sprintf(
            'collection \'%s\' not found in db \'%s\'.',
            $wrongCollection,
            $this->getDb(),
        );

        self::assertEquals(new Failure(\sprintf('MongoDB collection check failed: %s', $msg)), $result);
    }

    #[Test]
    public function shouldFailedCheckSettingsDoNotEqual(): void
    {
        $setting = 'options.validator.$jsonSchema';

        $expectedSettings = [
            $setting => [
                'a',
                'b',
                'c',
            ],
        ];

        $check = new MongoCollectionCheck(
            $this->getDsn(),
            $this->getCollection(),
            $expectedSettings
        );

        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('MongoDB collection check failed: the actual setting \'%s\' is different than expected.', $setting)), $result);
    }

    #[Test]
    public function shouldGetExtraParameters(): void
    {
        $check = new MongoCollectionCheck(
            $this->getDsn(),
            'test',
            $this->getExpectedSettings()
        );

        $check->check();

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'dsn'               => 'mongodb://root:***@diagnostic-mongo:27017/diagnostic',
            'collection'        => 'test',
            'expected settings' => [
                'options.validator.$jsonSchema' => [
                    'required'   => [
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
            ],
            'actual settings'   => [
                'name'    => 'test',
                'type'    => 'collection',
                'options' => [
                    'validator'        => [
                        '$jsonSchema' => [
                            'required'   => [
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
                    ],
                    'validationLevel'  => 'strict',
                    'validationAction' => 'error',
                ],
            ],
        ], $parameters);
    }
}
