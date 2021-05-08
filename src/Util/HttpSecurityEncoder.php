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

namespace FiveLab\Component\Diagnostic\Util;

/**
 * Service for encode secured parts in HTTP parameters.
 */
class HttpSecurityEncoder
{
    /**
     * Encode the URI for security issues.
     *
     * @param string $url
     *
     * @return string
     */
    public function encodeUri(string $url): string
    {
        $parts = \parse_url($url);

        if (false === $parts) {
            return $url;
        }

        if (\array_key_exists('pass', $parts)) {
            $parts['pass'] = '***';
        }

        $encodedUrl = '';

        if (\array_key_exists('scheme', $parts)) {
            $encodedUrl .= $parts['scheme'].'://';
        }

        if (\array_key_exists('user', $parts)) {
            $encodedUrl .= $parts['user'];
        }

        if (\array_key_exists('pass', $parts)) {
            $encodedUrl .= ':'.$parts['pass'];
        }

        if (\array_key_exists('user', $parts) || \array_key_exists('pass', $parts)) {
            $encodedUrl .= '@';
        }

        if (\array_key_exists('host', $parts)) {
            $encodedUrl .= $parts['host'];
        }

        if (\array_key_exists('port', $parts)) {
            $encodedUrl .= ':'.$parts['port'];
        }

        if (\array_key_exists('path', $parts)) {
            $encodedUrl .= $parts['path'];
        }

        if (\array_key_exists('query', $parts)) {
            $encodedUrl .= '?'.$parts['query'];
        }

        if (\array_key_exists('fragment', $parts)) {
            $encodedUrl .= '#'.$parts['fragment'];
        }

        return $encodedUrl;
    }

    /**
     * Encode headers
     *
     * @param array<string, string|array> $headers
     *
     * @return array<string, string|array>
     */
    public function encodeHeaders(array $headers): array
    {
        $encodedHeaders = [];

        foreach ($headers as $key => $value) {
            if (\strtolower($key) === 'authorization') {
                if (\is_array($value)) {
                    $value = ['***'];
                } else {
                    $value = '***';
                }
            }

            $encodedHeaders[$key] = $value;
        }

        return $encodedHeaders;
    }
}
