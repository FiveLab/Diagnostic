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

namespace FiveLab\Component\Diagnostic\Check\Elasticsearch;

readonly class ElasticsearchConnectionParameters
{
    public function __construct(public string $host, public int $port = 9200, public ?string $username = null, public ?string $password = null, public bool $ssl = false)
    {
    }

    public static function fromDsn(string $dsn): self
    {
        $parts = \parse_url($dsn);

        if (!$host = $parts['host'] ?? null) {
            throw new \InvalidArgumentException(\sprintf(
                'Missed "host" in DSN "%s".',
                $dsn
            ));
        }

        return new self(
            $host,
            (int) ($parts['port'] ?? 9200),
            $parts['user'] ?? null,
            $parts['pass'] ?? null,
            ($parts['scheme'] ?? 'http') === 'https'
        );
    }

    public function getDsn(bool $maskedPassword = false): string
    {
        $userPass = '';

        if ($this->username) {
            $userPass = \sprintf('%s:%s@', $this->username, $maskedPassword ? '***' : $this->password);
        }

        return \sprintf(
            '%s://%s%s:%s',
            $this->ssl ? 'https' : 'http',
            $userPass,
            $this->host,
            $this->port
        );
    }
}
