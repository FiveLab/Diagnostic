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

use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoExtendedConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoSchemaCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMongoTestCase;

class MongoSchemaCheckTest extends AbstractMongoTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->connectionParametersProvided()) {
            self::markTestSkipped('MongoDB is not configured.');
        }
    }

    /**
     * @test
     */
    public function testSuccessfulCheck(): void
    {
        $check = new MongoSchemaCheck(
            $this->getExtendedConnectionParameters(),
            $this->getSchema()
        );

        $result = $check->check();

        self::assertEquals(new Success('Successful MongoDB collection schema check.'), $result);
    }

    /**
     * @test
     */
    public function testFailedCheckConnectionFailed(): void
    {
        $invalidHost = $this->getHost().'_some';

        $connectionParameters = new MongoExtendedConnectionParameters(
            $this->getUsername(),
            $this->getPassword(),
            $this->getDb(),
            $this->getCollection(),
            new MongoConnectionParameters(
                $invalidHost,
                $this->getPort(),
                false
            )
        );

        $check = new MongoSchemaCheck(
            $connectionParameters,
            $this->getSchema()
        );

        $result = $check->check();

        $msg = \sprintf('MongoDB connection failed: No suitable servers found (`serverSelectionTryOnce` set): [Failed to resolve \'%s\'].', $invalidHost);

        self::assertEquals(new Failure($msg), $result);
    }

    /**
     * @test
     */
    public function testFailedCheckExpectedSchemaDoesNotEqual(): void
    {
        $schema = [
            'a' => [
                'b',
                'c',
            ],
        ];

        $check = new MongoSchemaCheck(
            $this->getExtendedConnectionParameters(),
            \json_encode($schema)
        );

        $result = $check->check();

        self::assertEquals(new Failure('MongoDB collection schema check failed: expected json-schema does not equal the actual one.'), $result);
    }

    /**
     * @test
     */
    public function testInvalidJsonSchema(): void
    {
        $invalidJsonSchema = '{"a":{"b","c"}}';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid json-schema given.');

        new MongoSchemaCheck(
            $this->getExtendedConnectionParameters(),
            $invalidJsonSchema
        );
    }

    /**
     * @test
     */
    public function testGetExtraParameters(): void
    {
        $extendedConnectionParameters = new MongoExtendedConnectionParameters(
            'user',
            'pass',
            'db',
            'collection',
            new MongoConnectionParameters(
                'mongo',
                27017,
                true
            )
        );

        $check = new MongoSchemaCheck(
            $extendedConnectionParameters,
            $this->getSchema()
        );

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host' => 'mongo',
            'port' => 27017,
            'ssl'  => 'yes',
            'user' => 'user',
            'pass' => '***',
            'db' => 'db',
            'collection' => 'collection',
        ], $parameters);
    }
}
