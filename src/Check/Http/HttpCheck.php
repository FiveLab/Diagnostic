<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Http;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Simple check connect to resource by HTTP.
 */
class HttpCheck implements CheckInterface
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
     * @var array
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
     * @var string|null
     */
    private ?string $expectedBody;

    /**
     * @var HttpAdapterInterface
     */
    private HttpAdapterInterface $http;

    /**
     * @var HttpSecurityEncoder
     */
    private HttpSecurityEncoder $httpSecurityEncoder;

    /**
     * @var string
     */
    private string $responseBody;

    /**
     * Constructor.
     *
     * @param string                   $method
     * @param string                   $url
     * @param array                    $headers
     * @param string                   $body
     * @param int                      $expectedStatusCode
     * @param string|null              $expectedBody
     * @param HttpAdapter|null         $http
     * @param HttpSecurityEncoder|null $securityEncoder
     */
    public function __construct(string $method, string $url, array $headers, string $body, int $expectedStatusCode, string $expectedBody = null, HttpAdapter $http = null, HttpSecurityEncoder $securityEncoder = null)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->expectedStatusCode = $expectedStatusCode;
        $this->expectedBody = $expectedBody;
        $this->http = $http ?: new HttpAdapter();
        $this->httpSecurityEncoder = $securityEncoder ?: new HttpSecurityEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
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

    /**
     * {@inheritdoc}
     */
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
