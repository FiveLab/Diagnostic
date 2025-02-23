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
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use Psr\Http\Client\ClientExceptionInterface;

class HttpCheck implements CheckInterface
{
    private readonly HttpAdapterInterface $http;
    private readonly HttpSecurityEncoder $httpSecurityEncoder;
    private ?string $responseBody = null;

    /**
     * Constructor.
     *
     * @param string                   $method
     * @param string                   $url
     * @param array<string, string>    $headers
     * @param string                   $body
     * @param int                      $expectedStatusCode
     * @param string|null              $expectedBody
     * @param HttpAdapter|null         $http
     * @param HttpSecurityEncoder|null $securityEncoder
     */
    public function __construct(
        private readonly string  $method,
        private readonly string  $url,
        private readonly array   $headers,
        private readonly string  $body,
        private readonly int     $expectedStatusCode,
        private readonly ?string $expectedBody = null,
        ?HttpAdapter             $http = null,
        ?HttpSecurityEncoder     $securityEncoder = null
    ) {
        $this->http = $http ?: new HttpAdapter();
        $this->httpSecurityEncoder = $securityEncoder ?: new HttpSecurityEncoder();
    }

    public function check(): Result
    {
        $request = $this->http->createRequest($this->method, $this->url, $this->headers, $this->body);

        try {
            $response = $this->http->sendRequest($request);

            $this->responseBody = (string) $response->getBody();
        } catch (ClientExceptionInterface $e) {
            return new Failure(\sprintf(
                'Fail send HTTP request. Error: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        if ($response->getStatusCode() !== $this->expectedStatusCode) {
            return new Failure(\sprintf(
                'The server return "%d" status code, but we expect "%d" status code.',
                $response->getStatusCode(),
                $this->expectedStatusCode
            ));
        }

        if ($this->expectedBody && $this->responseBody !== $this->expectedBody) {
            return new Failure('The server return wrong response. We expect another content.');
        }

        return new Success('Success get response and check all options.');
    }

    public function getExtraParameters(): array
    {
        $uri = $this->httpSecurityEncoder->encodeUri($this->url);
        $headers = $this->httpSecurityEncoder->encodeHeaders($this->headers);

        $parameters = [
            'method'      => $this->method,
            'url'         => $uri,
            'headers'     => \json_encode($headers, JSON_THROW_ON_ERROR),
            'body'        => $this->body,
            'status code' => $this->expectedStatusCode,
        ];

        if ($this->expectedBody) {
            $parameters['expected body'] = $this->expectedBody;
            $parameters['actual body'] = $this->responseBody;
        }

        return $parameters;
    }
}
