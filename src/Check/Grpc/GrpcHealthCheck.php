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
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use Grpc\Call;
use Grpc\Channel;
use Grpc\Timeval;

/**
 *  Performs grpc health-check.
 */
class GrpcHealthCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $host;

    /**
     * @var string
     */
    private string $port;

    /**
     * @var string
     */
    private string $service;

    /**
     * Constructor.
     *
     * @param string $host
     * @param string $port
     * @param string $service
     */
    public function __construct(string $host, string $port, string $service)
    {
        $this->host = $host;
        $this->port = $port;
        $this->service = $service;
    }

    /**
     * @return ResultInterface
     */
    public function check(): ResultInterface
    {
        try {
            $call = new Call(
                new Channel($this->host . ':' . $this->port),
                'grpc.health.v1.Health.Check',
                new Timeval(10000000)
            );

            $call->startBatch(
                [
                    'service' => $this->service,
                ]
            );
        } catch (\Exception $e) {
            return new Failure(\sprintf('Grpc health-check failed: %s.', $e->getMessage()));
        }

        return new Success('Successful grpc health-check.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [];
    }
}
