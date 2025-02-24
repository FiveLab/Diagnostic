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

namespace FiveLab\Component\Diagnostic\Util\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

readonly class HttpAdapter implements HttpAdapterInterface
{
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(?ClientInterface $client = null, ?RequestFactoryInterface $requestFactory = null, ?StreamFactoryInterface $streamFactory = null)
    {
        if (!$client && !\class_exists(Client::class)) {
            throw new \RuntimeException('Can\'t create HTTP client because package "guzzlehttp/guzzle" not installed. Please install it for use HTTP.');
        }

        if (!$requestFactory && !\class_exists(HttpFactory::class)) {
            throw new \RuntimeException('Can\'t create HTTP request factory because package "guzzlehttp/psr7" not installed. Please install it for use HTTP.');
        }

        if (!$streamFactory && !\class_exists(HttpFactory::class)) {
            throw new \RuntimeException('Can\'t create HTTP stream factory because package "guzzle/psr7" not installed. Please install it for use HTTP. Please install it for use HTTP.');
        }

        $this->client = $client ?: new Client(['http_errors' => false]);
        $this->requestFactory = $requestFactory ?: new HttpFactory();
        $this->streamFactory = $streamFactory ?: new HttpFactory();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request);
    }

    public function createRequest(string $method, string $url, array $headers = [], ?string $body = null): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $url);

        if ($body) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
