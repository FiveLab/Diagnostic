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

namespace FiveLab\Component\Diagnostic\Tests\Check\Elasticsearch;

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionCheck;
use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractElasticsearchTestCase;

class ElasticsearchConnectionCheckTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithElasticsearch()) {
            self::markTestSkipped('The ElasticSearch is not configured.');
        }
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $check = new ElasticsearchConnectionCheck($this->getConnectionParameters());

        $result = $check->check();

        self::assertEquals(new Success('Success connect to ElasticSearch and send ping request.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckIfHostIsInvalid(): void
    {
        $connectionParameters = new ElasticsearchConnectionParameters(
            $this->getElasticsearchHost().'_some',
            $this->getElasticsearchPort(),
            $this->getElasticsearchUser(),
            $this->getElasticsearchPassword(),
            false
        );

        $check = new ElasticsearchConnectionCheck($connectionParameters);

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to ElasticSearch: No alive nodes found in your cluster.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParametersWithoutUserAndPass(): void
    {
        $check = new ElasticsearchConnectionCheck(new ElasticsearchConnectionParameters('some', 9201));

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host' => 'some',
            'port' => 9201,
            'ssl'  => 'no',
        ], $parameters);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParametersWithUserAndPass(): void
    {
        $connectionParameters = new ElasticsearchConnectionParameters(
            'foo',
            9202,
            'some',
            'bar-foo',
            true
        );

        $check = new ElasticsearchConnectionCheck($connectionParameters);

        $parameters = $check->getExtraParameters();

        self::assertEquals([
            'host' => 'foo',
            'port' => 9202,
            'ssl'  => 'yes',
            'user' => 'some',
            'pass' => '***',
        ], $parameters);
    }
}
