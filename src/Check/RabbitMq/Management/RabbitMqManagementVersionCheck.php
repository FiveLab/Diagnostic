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

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\Management;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;
use Psr\Http\Client\ClientExceptionInterface;

class RabbitMqManagementVersionCheck implements CheckInterface
{
    private HttpAdapterInterface $http;
    private VersionComparatorInterface $versionComparator;
    private ?string $actualVersion = null;

    public function __construct(
        private RabbitMqConnectionParameters $connectionParameters,
        private string                       $expectedVersion,
        ?VersionComparatorInterface          $versionComparator = null,
        ?HttpAdapterInterface                $http = null
    ) {
        $this->http = $http ?: new HttpAdapter();
        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
    }

    public function check(): Result
    {
        $url = \sprintf(
            '%s/api/overview',
            $this->connectionParameters->getDsn(true, false)
        );

        $request = $this->http->createRequest('GET', $url, [
            'accept' => 'application/json',
        ]);

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return new Failure(\sprintf(
                'Fail connect to RabbitMQ Management API. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        if ($response->getStatusCode() !== 200) {
            return new Failure(\sprintf(
                'Fail connect to RabbitMQ Management API. Return wrong status code - %d.',
                $response->getStatusCode()
            ));
        }

        $json = \json_decode((string) $response->getBody(), flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);

        $this->actualVersion = $json['rabbitmq_version'];

        if (!$this->versionComparator->satisfies((string) $this->actualVersion, $this->expectedVersion)) {
            return new Failure('Fail check RabbitMQ version.');
        }

        return new Success('Success check RabbitMQ version.');
    }

    public function getExtraParameters(): array
    {
        $params = [
            'dsn' => $this->connectionParameters->getDsn(true, true),
        ];

        return \array_merge($params, [
            'actual version'   => $this->actualVersion ?: '(null)',
            'expected version' => $this->expectedVersion ?: '(null)',
        ]);
    }
}
