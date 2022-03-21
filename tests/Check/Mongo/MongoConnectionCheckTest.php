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

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionCheck;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionCheck;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMongoTestCase;

class MongoConnectionCheckTest extends AbstractMongoTestCase
{
    /**
     * {@inheritDoc}
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
        $check = new MongoConnectionCheck($this->getConnectionParameters());

        $result = $check->check();

        self::assertEquals(new Success('Successful MongoDB connection.'), $result);
    }

    /**
     * @test
     */
    public function testFailedCheck(): void
    {
        $invalidHost = $this->getHost().'_some';

        $connectionParameters = new MongoConnectionParameters(
            $invalidHost,
            $this->getPort(),
            false
        );

        $check = new MongoConnectionCheck($connectionParameters);

        $result = $check->check();

        $msg = \sprintf('MongoDB connection failed: No suitable servers found (`serverSelectionTryOnce` set): [Failed to resolve \'%s\'].', $invalidHost);

        self::assertEquals(new Failure($msg), $result);
    }

    /**
     * @test
     */
    public function testGetExtraParameters(): void
    {
        $connectionParameters = new MongoConnectionParameters(
            'foo',
            27017,
            true
        );

        $check = new MongoConnectionCheck($connectionParameters);

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host' => 'foo',
            'port' => 27017,
            'ssl'  => 'yes',
        ], $parameters);
    }
}
