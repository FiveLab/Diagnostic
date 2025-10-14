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

namespace FiveLab\Component\Diagnostic\Check\ClickHouse;

use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Util\Dsn;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;

trait ClickHouseHelperTrait
{
    /**
     * Send request to ClickHouse Server.
     *
     * @param HttpAdapterInterface $http
     * @param Dsn                  $dsn
     * @param string               $uri
     *
     * @return Result|string
     */
    protected function sendRequest(HttpAdapterInterface $http, Dsn $dsn, string $uri): Result|string
    {
        $uri = \sprintf('%s/%s', $dsn->getDsn(), \ltrim($uri, '/'));

        $request = $http->createRequest('GET', $uri, [
            'authorization'         => 'Basic '.\base64_encode($dsn->username.':'.$dsn->password),
            'x-clickhouse-database' => $dsn->target,
        ]);

        try {
            $response = $http->sendRequest($request);
        } catch (\Throwable $error) {
            return new Failure(\sprintf(
                'Fail connect to %s with error: %s.',
                $dsn->getDsn(maskedPassword: true),
                \rtrim($error->getMessage(), '.')
            ));
        }

        if ($response->getStatusCode() !== 200) {
            return new Failure(\sprintf(
                'Receive wrong status code "%d", expected "200".',
                $response->getStatusCode()
            ));
        }

        return \trim((string) $response->getBody());
    }
}
