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

namespace FiveLab\Component\Diagnostic\Tests\Util;

use FiveLab\Component\Diagnostic\Util\HttpSecurityEncoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HttpSecurityEncoderTest extends TestCase
{
    private HttpSecurityEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new HttpSecurityEncoder();
    }

    #[Test]
    #[DataProvider('provideUris')]
    public function shouldSuccessEncodeUri(string $uri, string $expected): void
    {
        $result = $this->encoder->encodeUri($uri);

        self::assertEquals($expected, $result);
    }

    #[Test]
    #[DataProvider('provideHeaders')]
    public function shouldSuccessEncodeHeaders(array $headers, array $expected): void
    {
        $result = $this->encoder->encodeHeaders($headers);

        self::assertEquals($expected, $result);
    }

    public static function provideUris(): array
    {
        return [
            'default' => [
                'http://domain.com',
                'http://domain.com',
            ],

            'without scheme' => [
                'domain.com/path',
                'domain.com/path',
            ],

            'with port' => [
                'domain.com:8080/path',
                'domain.com:8080/path',
            ],

            'with query' => [
                'https://domain.com:8081/path?key=value',
                'https://domain.com:8081/path?key=value',
            ],

            'with fragment' => [
                'https://domain.com:8081/path?key=value#some',
                'https://domain.com:8081/path?key=value#some',
            ],

            'with username' => [
                'https://username@domain.com/ping',
                'https://username@domain.com/ping',
            ],

            'with password' => [
                'https://user:pass@domain.com/ping',
                'https://user:***@domain.com/ping',
            ],
        ];
    }

    public static function provideHeaders(): array
    {
        return [
            'simple' => [
                ['Content-Type' => 'text/plain', 'Content-Encoding' => 'gzip'],
                ['Content-Type' => 'text/plain', 'Content-Encoding' => 'gzip'],
            ],

            'with authorization as string' => [
                ['Content-Type' => 'application/json', 'Authorization' => 'bearer some-foo-token'],
                ['Content-Type' => 'application/json', 'Authorization' => '***'],
            ],

            'with authorization as array' => [
                ['Content-Type' => 'application/json', 'Authorization' => ['bearer some-foo-token', 'basic foo']],
                ['Content-Type' => 'application/json', 'Authorization' => ['***']],
            ],
        ];
    }
}
