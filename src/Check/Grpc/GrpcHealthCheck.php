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
    private string $hostname;

    /**
     * @var string
     */
    private string $service;

    /**
     * Constructor.
     *
     * @param string $hostname
     * @param string $service
     */
    public function __construct(string $hostname, string $service)
    {
        $this->hostname = $hostname;
        $this->service = $service;
    }

    /**
     * @return ResultInterface
     */
    public function check(): ResultInterface
    {
        try {
            $client = new HealthClient($this->hostname, ['credentials' => ChannelCredentials::createInsecure()]);
            list($responseData, $status) = $client->Check((new HealthCheckRequest())->setService($this->service))->wait();
        } catch (\Exception $e) {
            return new Failure(\sprintf(
                'Grpc health-check failed: %s.',
                $e->getMessage()
            ));
        }

        if ($status->code !== 0) {
            return new Failure(\sprintf(
                'Grpc health-check failed: %s.',
                $status->details
            ));
        }

        $serviceStatus = $responseData->getStatus();
        if ($serviceStatus == ServingStatus::SERVING) {
            return new Success('Successful grpc health-check.');
        } else {
            return new Failure(\sprintf(
                'Grpc health-check failed: \'%s\' status is \'%s\'.',
                $this->service,
                ServingStatus::name($serviceStatus)
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'hostname' => $this->hostname,
            'service' => $this->service,
        ];
    }
}
