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
use FiveLab\Component\Diagnostic\Result\Warning;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

readonly class RabbitMqManagementQueueCheck implements CheckInterface
{
    private HttpAdapterInterface $http;

    public function __construct(
        private RabbitMqConnectionParameters $connectionParameters,
        private string                       $queueName,
        private ?int                         $maxMessages = null,
        private ?int                         $minMessages = null,
        private ?int                         $maxWarningPercentage = null,
        ?HttpAdapterInterface                $http = null
    ) {
        if ($this->maxWarningPercentage < 0 || $this->maxWarningPercentage > 100) {
            throw new \InvalidArgumentException('$maxWarningPercentage must be between 0 and 100');
        }

        $this->http = $http ?: new HttpAdapter();
    }

    public function check(): Result
    {
        $url = \sprintf(
            '%s/api/queues/%s/%s',
            $this->connectionParameters->getDsn(true, false),
            \urlencode($this->connectionParameters->vhost),
            \urlencode($this->queueName)
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
            return new Failure('Queue was not found.');
        }

        if ($response->getStatusCode() !== 200) {
            return new Failure(\sprintf(
                'Fail connect to RabbitMQ Management API. Return wrong status code - %d.',
                $response->getStatusCode()
            ));
        }

        $result = $this->checkMessageLimits($response);

        if ($result) {
            return $result;
        }

        return new Success('Success check queue via RabbitMQ Management API.');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn'                    => $this->connectionParameters->getDsn(true, true),
            'vhost'                  => $this->connectionParameters->vhost,
            'queue'                  => $this->queueName,
            'max_messages'           => $this->maxMessages,
            'min_messages'           => $this->minMessages,
            'max_warning_percentage' => $this->maxWarningPercentage,
        ];
    }

    private function checkMessageLimits(ResponseInterface $response): ?Result
    {
        if (!$this->maxMessages && !$this->minMessages) {
            return null;
        }

        $queueDetails = \json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $queuedMessages = $queueDetails['messages'] ?? 0;

        $maxMessagesResult = $this->checkForMaxMessages($queuedMessages);

        if ($maxMessagesResult) {
            return $maxMessagesResult;
        }

        $minMessagesResult = $this->checkForMinMessages($queuedMessages);

        if ($minMessagesResult) {
            return $minMessagesResult;
        }

        return null;
    }

    private function checkForMaxMessages(int $queuedMessages): ?Result
    {
        if (!$this->maxMessages) {
            return null;
        }

        switch (true) {
            case $queuedMessages > $this->maxMessages:
                return new Failure(\sprintf(
                    '%d messages found! Max allowed %d for queue %s',
                    $queuedMessages,
                    $this->maxMessages,
                    $this->queueName
                ));

            case $this->maxWarningPercentage && ($queuedMessages > ($this->maxMessages * $this->maxWarningPercentage) / 100):
                return new Warning(\sprintf(
                    'Warning! %d messages found. Max %d for queue %s',
                    $queuedMessages,
                    $this->maxMessages,
                    $this->queueName
                ));
        }

        return null;
    }

    private function checkForMinMessages(int $queuedMessages): ?Result
    {
        if (!$this->minMessages || $queuedMessages >= $this->minMessages) {
            return null;
        }

        return new Failure(\sprintf(
            '%d messages found! Minimum required %d for queue %s',
            $queuedMessages,
            $this->minMessages,
            $this->queueName
        ));
    }
}
