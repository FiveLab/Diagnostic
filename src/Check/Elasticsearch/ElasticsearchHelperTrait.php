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
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;

trait ElasticsearchHelperTrait
{
    /**
     * Send request to elasticsearch API.
     *
     * @param HttpAdapterInterface              $http
     * @param ElasticsearchConnectionParameters $connectionParameters
     * @param string                            $uri
     *
     * @return Result|array<string|int, mixed>
     */
    protected function sendRequest(HttpAdapterInterface $http, ElasticsearchConnectionParameters $connectionParameters, string $uri): Result|array
    {
        $uri = \sprintf('%s/%s', \rtrim($connectionParameters->getDsn(), \ltrim($uri, '/')), $uri);

        $request = $http->createRequest('GET', $uri, [
            'accept' => 'application/json',
        ]);

        try {
            $response = $http->sendRequest($request);

            $json = \json_decode((string) $response->getBody(), flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        } catch (\Throwable $error) {
            return new Failure(\sprintf(
                'Fail connect to %s with error: %s.',
                $connectionParameters->getDsn(),
                \rtrim($error->getMessage(), '.')
            ));
        }

        $status = $json['status'] ?? null;

        if (404 === $status) {
            $reason = $json['error']['reason'] ?? 'unknown';

            return new Failure(\sprintf('Fail check: %s', $reason));
        }

        return $json;
    }
}
