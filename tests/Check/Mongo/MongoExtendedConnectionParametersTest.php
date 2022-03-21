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

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionParameters;
use FiveLab\Component\Diagnostic\Check\Mongo\MongoExtendedConnectionParameters;
use JetBrains\PhpStorm\ArrayShape;
use PHPUnit\Framework\TestCase;

class MongoExtendedConnectionParametersTest extends TestCase
{
    public function testGetDsn(): void
    {
        $extendedConnectionParameters = new MongoExtendedConnectionParameters(
            'user',
            'pass',
            'db',
            'collection',
            new MongoConnectionParameters(
                'mongo'
            )
        );

        self::assertEquals('mongodb://user:pass@mongo:27017/db', $extendedConnectionParameters->getDsn());
    }
}
