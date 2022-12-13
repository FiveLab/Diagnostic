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

namespace FiveLab\Component\Diagnostic\Check\OpenSearch;

use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check success connect to OpenSearch.
 *
 * Critical: the client library for ping request only check via HEAD method.
 *           As result if you connect to any services via ssl, check return success result.
 *           For fix this, please use additional check for check version of OpenSearch (as an example).
 */
class OpenSearchConnectionCheck implements CheckInterface
{
    /**
     * @var OpenSearchConnectionParameters
     */
    private OpenSearchConnectionParameters $connectionParameters;

    /**
     * Constructor.
     *
     * @param OpenSearchConnectionParameters $connectionParameters
     */
    public function __construct(OpenSearchConnectionParameters $connectionParameters)
    {
        $this->connectionParameters = $connectionParameters;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(Client::class)) {
            return new Failure('The package "opensearch-project/opensearch-php" is not installed.');
        }

        try {
            $client = ClientBuilder::create()
                ->setHosts([$this->connectionParameters->getDsn()])
                ->build();

            $ping = $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to OpenSearch: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        if ($ping) {
            return new Success('Success connect to OpenSearch and send ping request.');
        }

        return new Failure('Fail connect to OpenSearch or send ping request.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return OpenSearchHelper::convertConnectionParametersToArray($this->connectionParameters);
    }
}
