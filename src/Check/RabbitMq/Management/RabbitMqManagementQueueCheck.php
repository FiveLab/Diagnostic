<?php

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FiveLab\Component\Diagnostic\Check\RabbitMq\Management;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\RequestFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Check queue existence
 */
class RabbitMqManagementQueueCheck implements CheckInterface
{
    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var RabbitMqConnectionParameters
     */
    private $connectionParameters;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var int|null
     */
    private $maxMessages;

    /**
     * must be between 0 and 100
     * @var int
     */
    private $maxWarningPercentage;

    /**
     * @var int|null
     */
    private $minMessages;

    /**
     * @param RabbitMqConnectionParameters $connectionParameters
     * @param string                       $queueName
     * @param int|null                     $maxMessages
     * @param int|null                     $minMessages
     * @param int|null                     $maxWarningPercentage
     * @param HttpClient|null              $client
     * @param RequestFactory|null          $requestFactory
     */
    public function __construct(RabbitMqConnectionParameters $connectionParameters, string $queueName, int $maxMessages = null, int $minMessages = null, int $maxWarningPercentage = null, HttpClient $client = null, RequestFactory $requestFactory = null)
    {
        $this->connectionParameters = $connectionParameters;
        $this->queueName = $queueName;
        $this->maxMessages = $maxMessages;
        $this->minMessages = $minMessages;

        if ($maxWarningPercentage < 0 || $maxWarningPercentage > 100) {
            throw new \InvalidArgumentException('$maxWarningPercentage must be between 0 and 100');
        }

        $this->maxWarningPercentage = $maxWarningPercentage;

        $this->client = $client ?: HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $url = \sprintf(
            '%s/api/queues/%s/%s',
            $this->connectionParameters->getDsn(true, false),
            \urlencode($this->connectionParameters->getVhost()),
            \urlencode($this->queueName)
        );

        $request = $this->requestFactory->createRequest('GET', $url, [
            'accept' => 'application/json',
        ]);

        try {
            $response = $this->client->sendRequest($request);
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

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'dsn'                    => $this->connectionParameters->getDsn(true, true),
            'vhost'                  => $this->connectionParameters->getVhost(),
            'queue'                  => $this->queueName,
            'max_messages'           => $this->maxMessages,
            'min_messages'           => $this->minMessages,
            'max_warning_percentage' => $this->maxWarningPercentage,
        ];
    }

    /**
     * @param ResponseInterface $response
     *
     * @return ResultInterface|null
     */
    private function checkMessageLimits(ResponseInterface $response): ?ResultInterface
    {
        if (!\is_int($this->maxMessages) && !\is_int($this->minMessages)) {
            return null;
        }

        $queueDetails = \json_decode((string) $response->getBody(), true);

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

    /**
     * @param int $queuedMessages
     *
     * @return ResultInterface|null
     */
    private function checkForMaxMessages(int $queuedMessages): ?ResultInterface
    {
        if (!\is_int($this->maxMessages)) {
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

    /**
     * @param int $queuedMessages
     *
     * @return ResultInterface|null
     */
    private function checkForMinMessages(int $queuedMessages): ?ResultInterface
    {
        if (!\is_int($this->minMessages) || $queuedMessages >= $this->minMessages) {
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
