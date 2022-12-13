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

use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use FiveLab\Component\Diagnostic\Check\OpenSearch\OpenSearchConnectionParameters;
use PHPUnit\Framework\TestCase;

abstract class AbstractOpenSearchTestCase extends TestCase
{
    /**
     * Get the OpenSearch host
     *
     * @return string|null
     */
    protected function getOpenSearchHost(): ?string
    {
        return \getenv('OPENSEARCH_HOST') ?: null;
    }

    /**
     * Get OpenSearch port
     *
     * @return int|null
     */
    protected function getOpenSearchPort(): int
    {
        return \getenv('OPENSEARCH_PORT') ? (int) \getenv('OPENSEARCH_PORT') : 9200;
    }

    /**
     * Get the user for connect to OpenSearch
     *
     * @return string
     */
    protected function getOpenSearchUser(): ?string
    {
        return \getenv('OPENSEARCH_USER') ? \getenv('OPENSEARCH_USER') : null;
    }

    /**
     * Get the password for connect to OpenSearch
     *
     * @return string
     */
    protected function getOpenSearchPassword(): ?string
    {
        return \getenv('OPENSEARCH_PASSWORD') ? \getenv('OPENSEARCH_PASSWORD') : null;
    }

    /**
     * Is use SSL for connect to OpenSearch?
     *
     * @return bool
     */
    protected function isOpenSearchSsl(): bool
    {
        return \getenv('OPENSEARCH_SSL') ? true : false;
    }

    /**
     * Can testing with OpenSearch?
     *
     * @return bool
     */
    protected function canTestingWithOpenSearch(): bool
    {
        return $this->getOpenSearchHost() && $this->getOpenSearchPort();
    }

    /**
     * Get connection parameters to OpenSearch
     *
     * @return OpenSearchConnectionParameters
     */
    protected function getConnectionParameters(): OpenSearchConnectionParameters
    {
        return new OpenSearchConnectionParameters(
            $this->getOpenSearchHost(),
            $this->getOpenSearchPort(),
            $this->getOpenSearchUser(),
            $this->getOpenSearchPassword(),
            $this->isOpenSearchSsl()
        );
    }

    /**
     * Create a client
     *
     * @return Client
     */
    protected function createClient(): Client
    {
        $connectionParameters = $this->getConnectionParameters();

        return ClientBuilder::create()
            ->setHosts([$connectionParameters->getDsn()])
            ->build();
    }
}
