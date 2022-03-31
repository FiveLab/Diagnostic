<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Grpc\Health\V1;

/**
 */
class HealthClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Grpc\Health\V1\HealthCheckRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\Health\V1\HealthCheckResponse
     */
    public function Check(\Grpc\Health\V1\HealthCheckRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/grpc.health.v1.Health/Check',
        $argument,
        ['\Grpc\Health\V1\HealthCheckResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Grpc\Health\V1\HealthCheckRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\Health\V1\HealthCheckResponse
     */
    public function Watch(\Grpc\Health\V1\HealthCheckRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/grpc.health.v1.Health/Watch',
        $argument,
        ['\Grpc\Health\V1\HealthCheckResponse', 'decode'],
        $metadata, $options);
    }

}
