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

use FiveLab\Component\Diagnostic\Check\OpenSearch\OpenSearchConnectionParameters;
use FiveLab\Component\Diagnostic\Check\OpenSearch\OpenSearchVersionCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractOpenSearchTestCase;

class OpenSearchVersionCheckTest extends AbstractOpenSearchTestCase
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
    public function shouldSuccessCheckVersions(): void
    {
        $check = new OpenSearchVersionCheck($this->getConnectionParameters(), '2.4.0');

        $result = $check->check();

        self::assertEquals(new Success('Success check OpenSearch version.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckForOpenSearchVersion(): void
    {
        $check = new OpenSearchVersionCheck($this->getConnectionParameters(), '~6.7');

        $result = $check->check();

        self::assertEquals(new Failure('Fail check OpenSearch version.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckLuceneVersion(): void
    {
        $check = new OpenSearchVersionCheck($this->getConnectionParameters(), null, '~6.0');

        $result = $check->check();

        self::assertEquals(new Failure('Fail check Lucene version.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailIfCannotConnect(): void
    {
        $check = new OpenSearchVersionCheck(new OpenSearchConnectionParameters('some', 9201));

        $result = $check->check();

        self::assertEquals(new Failure('Fail connect to OpenSearch: No alive nodes found in your cluster.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetParameters(): void
    {
        $check = new OpenSearchVersionCheck($this->getConnectionParameters(), '~6.8.0', '~7.0');

        $check->check();
        $parameters = $check->getExtraParameters();

        self::assertEquals($this->getOpenSearchHost(), $parameters['host']);
        self::assertEquals($this->getOpenSearchPort(), $parameters['port']);
        self::assertEquals($this->isOpenSearchSsl() ? 'yes' : 'no', $parameters['ssl']);

        self::assertArrayHasKey('actual version', $parameters);
        self::assertArrayHasKey('expected version', $parameters);
        self::assertArrayHasKey('actual lucene version', $parameters);
        self::assertArrayHasKey('expected lucene version', $parameters);

        self::assertNotEmpty($parameters['actual version']);
        self::assertEquals('~6.8.0', $parameters['expected version']);
        self::assertNotEmpty($parameters['actual lucene version']);
        self::assertEquals('~7.0', $parameters['expected lucene version']);
    }
}
