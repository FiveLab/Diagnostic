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
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractMongoTestCase;
use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;

class MongoConnectionCheckTest extends AbstractMongoTestCase
{
    protected function setUp(): void
    {
        if (!$this->connectionParametersProvided()) {
            self::markTestSkipped('MongoDB is not configured.');
        }
    }

    #[Test]
    public function shouldSuccessfulCheck(): void
    {
        $check = new MongoConnectionCheck($this->getDsn());

        $result = $check->check();

        self::assertEquals(new Success('Successful MongoDB connection.'), $result);
    }

    #[Test]
    public function shouldFailedCheck(): void
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

        $check = new MongoConnectionCheck($dsn);

        $result = $check->check();

        $msg = \sprintf('MongoDB connection failed: No suitable servers found (`serverSelectionTryOnce` set): [Failed to resolve \'%s\'].', $invalidHost);

        self::assertInstanceOf(Failure::class, $result);
        self::assertStringStartsWith($msg, $result->message);
    }

    #[Test]
    public function shouldGetExtraParameters(): void
    {
        $check = new MongoConnectionCheck(
            new Dsn(
                'mongodb',
                'mongo',
                '/db',
                27017,
                'user',
                'pass',
            ),
        );

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'dsn' => 'mongodb://user:***@mongo:27017/db',
        ], $parameters);
    }
}
