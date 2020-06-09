<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Http;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\RequestFactory;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Simple check connect to resource by HTTP.
 */
class HttpCheck implements CheckInterface
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
     * @var string
     */
    private $body;

    /**
     * @var int
     */
    private $expectedStatusCode;

    /**
     * @var string
     */
    private $expectedBody;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var HttpSecurityEncoder
     */
    private $httpSecurityEncoder;

    /**
     * @var string
     */
    private $responseBody;

    /**
     * Constructor.
     *
     * @param string              $method
     * @param string              $url
     * @param array               $headers
     * @param string              $body
     * @param integer             $expectedStatusCode
     * @param string              $expectedBody
     * @param HttpClient          $client
     * @param RequestFactory      $requestFactory
     * @param HttpSecurityEncoder $securityEncoder
     */
    public function __construct(string $method, string $url, array $headers, string $body, int $expectedStatusCode, string $expectedBody = null, HttpClient $client = null, RequestFactory $requestFactory = null, HttpSecurityEncoder $securityEncoder = null)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->expectedStatusCode = $expectedStatusCode;
        $this->expectedBody = $expectedBody;
        $this->client = $client ?: HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
        $this->httpSecurityEncoder = $securityEncoder ?: new HttpSecurityEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $request = $this->requestFactory->createRequest($this->method, $this->url, $this->headers, $this->body);

        try {
            $response = $this->client->sendRequest($request);

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
            'headers'     => \json_encode($headers),
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
