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

namespace FiveLab\Component\Diagnostic\Check\Http;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Check the pingable resources with application name, roles and version.
 */
class PingableHttpCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $method;

    /**
     * @var string
     */
    private string $url;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @var string
     */
    private string $body;

    /**
     * @var int
     */
    private int $expectedStatusCode;

    /**
     * @var string
     */
    private string $expectedApplicationName;

    /**
     * @var array<string>
     */
    private array $expectedApplicationRoles;

    /**
     * @var string|null
     */
    private ?string $expectedVersion;

    /**
     * @var HttpAdapterInterface
     */
    private HttpAdapterInterface $http;

    /**
     * @var VersionComparatorInterface
     */
    private VersionComparatorInterface $versionComparator;

    /**
     * @var HttpSecurityEncoder
     */
    private HttpSecurityEncoder $httpSecurityEncoder;

    /**
     * Constructor.
     *
     * @param string                          $method
     * @param string                          $url
     * @param array<string, string>           $headers
     * @param string                          $body
     * @param int                             $expectedStatusCode
     * @param string                          $expectedApplicationName
     * @param array<string>                   $expectedApplicationRoles
     * @param string|null                     $expectedVersion
     * @param HttpAdapterInterface|null       $http
     * @param VersionComparatorInterface|null $versionComparator
     * @param HttpSecurityEncoder|null        $httpSecurityEncoder
     */
    public function __construct(string $method, string $url, array $headers, string $body, int $expectedStatusCode, string $expectedApplicationName, array $expectedApplicationRoles = [], string $expectedVersion = null, HttpAdapterInterface $http = null, VersionComparatorInterface $versionComparator = null, HttpSecurityEncoder $httpSecurityEncoder = null)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->expectedStatusCode = $expectedStatusCode;
        $this->expectedApplicationName = $expectedApplicationName;
        $this->expectedApplicationRoles = $expectedApplicationRoles;
        $this->expectedVersion = $expectedVersion;
        $this->http = $http ?: new HttpAdapter();
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
        $request = $this->http->createRequest($this->method, $this->url, $this->headers, $this->body);

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return new Failure(\sprintf(
                'Fail send HTTP request. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        $body = (string) $response->getBody();

        if (!$body) {
            return new Failure('Server returns empty response.');
        }

        if ($response->getStatusCode() !== $this->expectedStatusCode) {
            return new Failure(\sprintf(
                'The server return "%d" status code, but we expect "%d" status code.',
                $response->getStatusCode(),
                $this->expectedStatusCode
            ));
        }

        try {
            $json = \json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            return new Failure(\sprintf(
                'Cannot decode the response to JSON. Error: %s.',
                $error->getMessage()
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
            'headers'             => \json_encode($headers, JSON_THROW_ON_ERROR),
            'body'                => $this->body,
            'status code'         => $this->expectedStatusCode,
            'application name'    => $this->expectedApplicationName,
            'application roles'   => \implode(', ', $this->expectedApplicationRoles),
            'application version' => $this->expectedVersion,
        ];
    }
}
