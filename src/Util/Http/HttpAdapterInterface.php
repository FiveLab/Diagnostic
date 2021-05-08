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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An adapter for work wth HTTP layers.
 */
interface HttpAdapterInterface
{
    /**
     * Send request
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;

    /**
     * Create a request
     *
     * @param string                $method
     * @param string                $url
     * @param array<string, string> $headers
     * @param string|null           $body
     *
     * @return RequestInterface
     */
    public function createRequest(string $method, string $url, array $headers = [], string $body = null): RequestInterface;
}
