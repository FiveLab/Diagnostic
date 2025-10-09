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

use FiveLab\Component\Diagnostic\Util\Dsn;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    #[Test]
    public function shouldSuccessParse(): void
    {
        $dsn = Dsn::parse('http://foo-bar.local/p1');

        self::assertEquals('http', $dsn->scheme);
        self::assertEquals('foo-bar.local', $dsn->host);
        self::assertEquals('/p1', $dsn->path);
        self::assertEquals(null, $dsn->port);
        self::assertEquals(null, $dsn->username);
        self::assertEquals(null, $dsn->password);
        self::assertEquals(null, $dsn->options);
    }

    #[Test]
    public function shouldSuccessParseWithAllData(): void
    {
        $dsn = Dsn::parse('tcp://bla:bar@foo-bar.local:8888/some-path?q=1&b[bar]=2');

        self::assertEquals('tcp', $dsn->scheme);
        self::assertEquals('foo-bar.local', $dsn->host);
        self::assertEquals('/some-path', $dsn->path);
        self::assertEquals(8888, $dsn->port);
        self::assertEquals('bla', $dsn->username);
        self::assertEquals('bar', $dsn->password);
        self::assertEquals(['q' => 1, 'b' => ['bar' => 2]], $dsn->options);
    }

    #[Test]
    public function shouldSuccessParseWithoutPath(): void
    {
        $dsn = Dsn::parse('http://foo-bar.local?q=1&b[bar]=2');

        self::assertEquals('/', $dsn->path);
    }

    #[Test]
    public function shouldSuccessParseWithDefaults(): void
    {
        $dsn = Dsn::parse('opensearch', [
            'scheme'  => 'http',
            'path'    => '/',
            'port'    => 9200,
            'user'    => 'foo',
            'pass'    => 'bar',
            'options' => ['timeout' => 5],
        ]);

        self::assertEquals('http', $dsn->scheme);
        self::assertEquals('opensearch', $dsn->host);
        self::assertEquals('/', $dsn->path);
        self::assertEquals(9200, $dsn->port);
        self::assertEquals('foo', $dsn->username);
        self::assertEquals('bar', $dsn->password);
        self::assertEquals(['timeout' => 5], $dsn->options);
    }

    #[Test]
    public function shouldFailParseIfSchemeNotPassed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missed "scheme" in DSN "opensearch".');

        Dsn::parse('opensearch');
    }

    #[Test]
    public function shouldFailParseIfHostNotPassed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can\'t parse DSN "tcp:///path?q=1".');

        Dsn::parse('tcp:///path?q=1');
    }

    #[Test]
    public function shouldSuccessParseIfPassPathAsSlash(): void
    {
        $dsn = Dsn::parse('tcp://rabbitmq//?q=1');

        self::assertEquals('//', $dsn->path);
    }

    #[Test]
    #[TestWith(['http://bla:bar@foo.local:8080/p1?q=1', false, false, false, 'http://bla:bar@foo.local:8080'])]
    #[TestWith(['http://bla:bar@foo.local:8080/p1?q=1', true, false, false, 'http://bla:bar@foo.local:8080/p1'])]
    #[TestWith(['http://bla:bar@foo.local:8080/p1?q=1', true, true, false, 'http://bla:bar@foo.local:8080/p1?q=1'])]
    #[TestWith(['http://bla:bar@foo.local:8080/p1?q=1', true, true, true, 'http://bla:***@foo.local:8080/p1?q=1'])]
    public function shouldSuccessGetDsn(string $dsn, bool $path, bool $query, bool $maskedPass, string $expected): void
    {
        $dsn = Dsn::parse($dsn);
        $result = $dsn->getDsn($path, $query, $maskedPass);

        self::assertEquals($expected, $result);
    }
}
