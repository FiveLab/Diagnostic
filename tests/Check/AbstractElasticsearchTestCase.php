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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters;
use PHPUnit\Framework\TestCase;

abstract class AbstractElasticsearchTestCase extends TestCase
{
    protected static function getElasticsearchHost(): ?string
    {
        return \getenv('ELASTICSEARCH_HOST') ?: null;
    }

    protected static function getElasticsearchPort(): int
    {
        return \getenv('ELASTICSEARCH_PORT') ? (int) \getenv('ELASTICSEARCH_PORT') : 9200;
    }

    protected static function getElasticsearchUser(): ?string
    {
        return \getenv('ELASTICSEARCH_USER') ? \getenv('ELASTICSEARCH_USER') : null;
    }

    protected static function getElasticsearchPassword(): ?string
    {
        return \getenv('ELASTICSEARCH_PASSWORD') ? \getenv('ELASTICSEARCH_PASSWORD') : null;
    }

    protected static function isElasticsearchSsl(): bool
    {
        return \getenv('ELASTICSEARCH_SSL') ? true : false;
    }

    protected static function canTestingWithElasticsearch(): bool
    {
        return self::getElasticsearchHost() && self::getElasticsearchPort();
    }

    protected static function getElasticsearchConnectionParameters(): ElasticsearchConnectionParameters
    {
        return new ElasticsearchConnectionParameters(
            self::getElasticsearchHost(),
            self::getElasticsearchPort(),
            self::getElasticsearchUser(),
            self::getElasticsearchPassword(),
            self::isElasticsearchSsl()
        );
    }

    protected static function getOpenSearchHost(): ?string
    {
        return \getenv('OPENSEARCH_HOST') ?: null;
    }

    protected static function getOpenSearchPort(): int
    {
        return \getenv('OPENSEARCH_PORT') ? (int) \getenv('OPENSEARCH_PORT') : 9200;
    }

    protected static function getOpenSearchUser(): ?string
    {
        return \getenv('OPENSEARCH_USER') ? \getenv('OPENSEARCH_USER') : null;
    }

    protected static function getOpenSearchPassword(): ?string
    {
        return \getenv('OPENSEARCH_PASSWORD') ? \getenv('OPENSEARCH_PASSWORD') : null;
    }

    protected static function isOpenSearchSsl(): bool
    {
        return \getenv('OPENSEARCH_SSL') ? true : false;
    }

    protected static function canTestingWithOpenSearch(): bool
    {
        return self::getOpenSearchHost() && self::getOpenSearchPort();
    }

    protected static function getOpenSearchConnectionParameters(): ElasticsearchConnectionParameters
    {
        return new ElasticsearchConnectionParameters(
            self::getOpenSearchHost(),
            self::getOpenSearchPort(),
            self::getOpenSearchUser(),
            self::getOpenSearchPassword(),
            self::isOpenSearchSsl()
        );
    }

    protected function markTestSkippedIfNotConfigured(string $target): void
    {
        if (!\in_array($target, ['Elasticsearch', 'Opensearch'], true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown target "%s".',
                $target
            ));
        }

        if ('Elasticsearch' === $target && !$this->canTestingWithElasticsearch()) {
            self::markTestSkipped('The Elasticsearch is not configured.');
        }

        if ('Opensearch' === $target && !$this->canTestingWithOpenSearch()) {
            self::markTestSkipped('The OpenSearch is not configured.');
        }
    }

    public static function provideTargets(): array
    {
        return [
            ['Elasticsearch', self::getElasticsearchConnectionParameters()],
            ['Opensearch', self::getOpenSearchConnectionParameters()],
        ];
    }
}
