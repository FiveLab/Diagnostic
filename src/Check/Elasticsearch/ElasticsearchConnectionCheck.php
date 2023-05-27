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

namespace FiveLab\Component\Diagnostic\Check\Elasticsearch;

use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check success connect to ElasticSearch.
 *
 * Critical: the client library for ping request only check via HEAD method.
 *           As result if you connect to any services via ssl, check return success result.
 *           For fix this, please use additional check for check version of ElasticSearch (as an example).
 */
class ElasticsearchConnectionCheck extends AbstractElasticsearchCheck
{
    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        try {
            $client = $this->createClient();

            $ping = $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to %s: %s.',
                $this->getEngineName(),
                \rtrim($e->getMessage(), '.')
            ));
        }

        if ($ping) {
            return new Success(\sprintf('Success connect to %s and send ping request.', $this->getEngineName()));
        }

        return new Failure(\sprintf('Fail connect to %s or send ping request.', $this->getEngineName()));
    }
}
