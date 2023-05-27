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
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Check exchange existence
 */
class RabbitMqManagementExchangeCheck implements CheckInterface
{
    /**
     * @var HttpAdapterInterface
     */
    private readonly HttpAdapterInterface $http;

    /**
     * @var string|null
     */
    private ?string $actualExchangeType = null;

    /**
     * Constructor.
     *
     * @param RabbitMqConnectionParameters $connectionParameters
     * @param string                       $exchangeName
     * @param string                       $exchangeType
     * @param HttpAdapterInterface|null    $http
     */
    public function __construct(
        private readonly RabbitMqConnectionParameters $connectionParameters,
        private readonly string                       $exchangeName,
        private readonly string                       $exchangeType,
        HttpAdapterInterface                          $http = null
    ) {
        $this->http = $http ?: new HttpAdapter();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): Result
    {
        $url = \sprintf(
            '%s/api/exchanges/%s/%s',
            $this->connectionParameters->getDsn(true, false),
            \urlencode($this->connectionParameters->vhost),
            \urlencode($this->exchangeName)
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

        if ($response->getStatusCode() === 404) {
            return new Failure('Exchange was not found.');
        }

        if ($response->getStatusCode() !== 200) {
            return new Failure(\sprintf(
                'Fail connect to RabbitMQ Management API. Return wrong status code - %d.',
                $response->getStatusCode()
            ));
        }

        $exchangeInfo = \json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->actualExchangeType = $exchangeInfo['type'];

        if ($this->actualExchangeType !== $this->exchangeType) {
            return new Failure('Invalid exchange types.');
        }

        return new Success('Success check exchange via RabbitMQ Management API.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'dsn'         => $this->connectionParameters->getDsn(true, true),
            'vhost'       => $this->connectionParameters->vhost,
            'exchange'    => $this->exchangeName,
            'type'        => $this->exchangeType,
            'actual type' => $this->actualExchangeType,
        ];
    }
}
