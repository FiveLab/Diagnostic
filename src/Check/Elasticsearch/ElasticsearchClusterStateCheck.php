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
use FiveLab\Component\Diagnostic\Result\Warning;

class ElasticsearchClusterStateCheck extends AbstractElasticsearchCheck
{
    public function check(): Result
    {
        try {
            $client = $this->createClient();

            $client->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Fail connect to ElasticSearch: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        try {
            $healthStatus = $client
                ->cluster()
                ->health();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Failed to get health status of the cluster : %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        return $this->parseClusterStatus($healthStatus);
    }

    /**
     * Parse cluster status
     *
     * @param array<string, mixed> $responseParams
     *
     * @return Result
     */
    private function parseClusterStatus(array $responseParams): Result
    {
        $default =  new Failure('Cluster status is undefined. Please check the logs.');

        if (\array_key_exists('status', $responseParams)) {
            return match ($responseParams['status']) {
                'green'  => new Success('Cluster status is GREEN.'),
                'yellow' => new Warning('Cluster status is YELLOW. Please check the logs.'),
                'red'    => new Failure('Cluster status is RED. Please check the logs.'),
                default  => $default,
            };
        }

        return $default;
    }
}
