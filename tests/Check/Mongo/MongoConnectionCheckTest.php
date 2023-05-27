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

use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionCheck;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMongoTestCase;
use PHPUnit\Framework\Attributes\Test;

class MongoConnectionCheckTest extends AbstractMongoTestCase
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

    #[Test]
    public function shouldSuccessfulCheck(): void
    {
        $check = new MongoConnectionCheck($this->getConnectionParameters());

        $result = $check->check();

        self::assertEquals(new Success('Successful MongoDB connection.'), $result);
    }

    #[Test]
    public function shouldFailedCheck(): void
    {
        $invalidHost = $this->getHost().'_some';

        $connectionParameters = new MongoConnectionParameters(
            $this->getProtocol(),
            $invalidHost,
            $this->getPort(),
            $this->getUsername(),
            $this->getPassword(),
            $this->getDb(),
        );

        $check = new MongoConnectionCheck($connectionParameters);

        $result = $check->check();

        $msg = \sprintf('MongoDB connection failed: No suitable servers found (`serverSelectionTryOnce` set): [Failed to resolve \'%s\'].', $invalidHost);

        self::assertEquals(new Failure($msg), $result);
    }

    #[Test]
    public function shouldGetExtraParameters(): void
    {
        $check = new MongoConnectionCheck(
            new MongoConnectionParameters(
                'mongodb',
                'mongo',
                27017,
                'user',
                'pass',
                'db',
            ),
        );

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'protocol' => 'mongodb',
            'host'     => 'mongo',
            'port'     => 27017,
            'user'     => 'user',
            'pass'     => '***',
            'db'       => 'db',
            'options'  => [],
        ], $parameters);
    }
}
