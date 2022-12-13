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

namespace FiveLab\Component\Diagnostic\Tests\Check\OpenSearch;

use FiveLab\Component\Diagnostic\Check\OpenSearch\OpenSearchConnectionCheck;
use FiveLab\Component\Diagnostic\Check\OpenSearch\OpenSearchConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractOpenSearchTestCase;

class OpenSearchConnectionCheckTest extends AbstractOpenSearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithOpenSearch()) {
            self::markTestSkipped('The OpenSearch is not configured.');
        }
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $check = new OpenSearchConnectionCheck($this->getConnectionParameters());

        $result = $check->check();

        self::assertEquals(new Success('Success connect to OpenSearch and send ping request.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckIfHostIsInvalid(): void
    {
        $connectionParameters = new OpenSearchConnectionParameters(
            $this->getOpenSearchHost().'_some',
            $this->getOpenSearchPort(),
            $this->getOpenSearchUser(),
            $this->getOpenSearchPassword(),
            false
        );

        $check = new OpenSearchConnectionCheck($connectionParameters);

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to OpenSearch: No alive nodes found in your cluster.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParametersWithoutUserAndPass(): void
    {
        $check = new OpenSearchConnectionCheck(new OpenSearchConnectionParameters('some', 9201));

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
        $connectionParameters = new OpenSearchConnectionParameters(
            'foo',
            9202,
            'some',
            'bar-foo',
            true
        );

        $check = new OpenSearchConnectionCheck($connectionParameters);

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
