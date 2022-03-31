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

namespace FiveLab\Component\Diagnostic\Check\Grpc;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use Grpc\Health\V1\HealthCheckRequest;
use Grpc\Health\V1\HealthCheckResponse\ServingStatus;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use Grpc\ChannelCredentials;
use Grpc\Health\V1\HealthClient;

/**
 *  Performs grpc health-check.
 */
class GrpcHealthCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $uri;

    /**
     * @var string
     */
    private string $service;

    /**
     * Constructor.
     *
     * @param string $uri
     * @param string $service
     */
    public function __construct(string $uri, string $service)
    {
        $this->uri = $uri;
        $this->service = $service;
    }

    /**
     * @return ResultInterface
     */
    public function check(): ResultInterface
    {
        $client = new HealthClient($this->uri, ['credentials' => ChannelCredentials::createInsecure()]);
        list($responseData,) = $client->Check((new HealthCheckRequest())->setService($this->service));

        $status = $responseData->getStatus();

        if ($status == ServingStatus::SERVING) {
            return new Success('Successful grpc health-check.');
        } else {
            return new Failure(\sprintf('Grpc health-check failed: requested service status is \'%s\'.', $status));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [];
    }
}
