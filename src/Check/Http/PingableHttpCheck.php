<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;

/**
 * Check the pingable resources with application name, roles and version.
 */
class PingableHttpCheck implements CheckInterface
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string|null
     */
    private $body;

    /**
     * @var int
     */
    private $expectedStatusCode;

    /**
     * @var string
     */
    private $expectedApplicationName;

    /**
     * @var array
     */
    private $expectedApplicationRoles;

    /**
     * @var string
     */
    private $expectedVersion;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var VersionComparatorInterface
     */
    private $versionComparator;

    /**
     * @var HttpSecurityEncoder
     */
    private $httpSecurityEncoder;

    /**
     * Constructor.
     *
     * @param string                     $method
     * @param string                     $url
     * @param array                      $headers
     * @param string|null                $body
     * @param int                        $expectedStatusCode
     * @param string                     $expectedApplicationName
     * @param array                      $expectedApplicationRoles
     * @param string                     $expectedVersion
     * @param Client                     $client
     * @param VersionComparatorInterface $versionComparator
     * @param HttpSecurityEncoder        $httpSecurityEncoder
     */
    public function __construct(string $method, string $url, array $headers, string $body, int $expectedStatusCode, string $expectedApplicationName, array $expectedApplicationRoles = [], string $expectedVersion = null, Client $client = null, VersionComparatorInterface $versionComparator = null, HttpSecurityEncoder $httpSecurityEncoder = null)
    {
        $this->client = $client ?: new Client();

        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->expectedStatusCode = $expectedStatusCode;
        $this->expectedApplicationName = $expectedApplicationName;
        $this->expectedApplicationRoles = $expectedApplicationRoles;
        $this->expectedVersion = $expectedVersion;
        $this->client = $client ?: new Client();
        $this->httpSecurityEncoder = $httpSecurityEncoder ?: new HttpSecurityEncoder();

        if ($this->expectedVersion) {
            $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $request = new Request($this->method, $this->url, $this->headers, $this->body);

        try {
            $response = $this->client->send($request, [
                RequestOptions::TIMEOUT => 5,
            ]);
        } catch (RequestException $e) {
            return new Failure(\sprintf(
                'Fail send HTTP request. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        $body = (string) $response->getBody();

        if (!$body) {
            return new Failure('Server returns empty response.');
        }

        \set_error_handler(function () {
        });

        $json = \json_decode((string) $body, true);

        \restore_error_handler();

        if ($response->getStatusCode() !== $this->expectedStatusCode) {
            return new Failure(\sprintf(
                'The server return "%d" status code, but we expect "%d" status code.',
                $response->getStatusCode(),
                $this->expectedStatusCode
            ));
        }

        if (null === $json && $error = \json_last_error_msg()) {
            return new Failure(\sprintf(
                'Cannot decode the response to JSON. Error: %s.',
                \rtrim($error, '.')
            ));
        }

        if (!\array_key_exists('application', $json)) {
            return new Failure('The "application" key is missing in response.');
        }

        if (!\array_key_exists('roles', $json)) {
            return new Failure('The "roles" key is missing in response.');
        }

        $applicationName = $json['application'];
        $applicationRoles = $json['roles'];
        $applicationVersion = $json['version'];

        if ($applicationName !== $this->expectedApplicationName) {
            return new Failure(\sprintf(
                'The server return "%s" application name, but we expect "%s" application name.',
                $applicationName,
                $this->expectedApplicationName
            ));
        }

        $noApplicationRoles = \array_diff($this->expectedApplicationRoles, $applicationRoles);

        if (\count($noApplicationRoles)) {
            return new Failure(\sprintf(
                'Missed "%s" application roles.',
                \implode('", "', $noApplicationRoles)
            ));
        }

        if ($this->expectedVersion && !$this->versionComparator->satisfies($applicationVersion, $this->expectedVersion)) {
            return new Failure(\sprintf(
                'The server return "%s" version, but we expect "%s".',
                $applicationVersion,
                $this->expectedVersion
            ));
        }

        return new Success('Success get response and check all options.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        $uri = $this->httpSecurityEncoder->encodeUri($this->url);
        $headers = $this->httpSecurityEncoder->encodeHeaders($this->headers);

        return [
            'method'              => $this->method,
            'url'                 => $uri,
            'headers'             => \json_encode($headers),
            'body'                => $this->body,
            'status code'         => $this->expectedStatusCode,
            'application name'    => $this->expectedApplicationName,
            'application roles'   => \implode(', ', $this->expectedApplicationRoles),
            'application version' => $this->expectedVersion,
        ];
    }
}
