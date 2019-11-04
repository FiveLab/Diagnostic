<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Http;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;

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
     * @var ClientInterface
     */
    private $client;

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
     * @param ClientInterface     $client
     * @param HttpSecurityEncoder $securityEncoder
     */
    public function __construct(string $method, string $url, array $headers, string $body, int $expectedStatusCode, string $expectedBody = null, ClientInterface $client = null, HttpSecurityEncoder $securityEncoder = null)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->expectedStatusCode = $expectedStatusCode;
        $this->expectedBody = $expectedBody;
        $this->client = $client ?: new Client();
        $this->httpSecurityEncoder = $securityEncoder ?: new HttpSecurityEncoder();
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $request = new Request($this->method, $this->url, $this->headers, $this->body);

        try {
            $response = $this->client->send($request, [
                RequestOptions::TIMEOUT     => 5,
                RequestOptions::HTTP_ERRORS => false,
            ]);

            $this->responseBody = $response->getBody()->getContents();
        } catch (GuzzleException $e) {
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
