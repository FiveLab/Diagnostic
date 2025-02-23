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

namespace FiveLab\Component\Diagnostic\Check\RabbitMq;

readonly class RabbitMqConnectionParameters
{
    public function __construct(
        public string $host,
        public int    $port,
        public string $username,
        public string $password,
        public string $vhost = '/',
        public bool   $ssl = false
    ) {
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

        if (!$port = $parts['port'] ?? null) {
            throw new \InvalidArgumentException(\sprintf(
                'Missed "port" in DSN "%s".',
                $dsn
            ));
        }

        $vhost = $parts['path'] ?? '/%2f';

        return new self(
            $host,
            (int) $port,
            $parts['user'] ?? 'guest',
            $parts['pass'] ?? 'guest',
            \urldecode(\ltrim($vhost, '/')),
            ($parts['scheme'] ?? 'http') === 'https'
        );
    }

    public function getDsn(bool $httpTransport, bool $maskedPassword): string
    {
        $prefix = $this->ssl ? 'ssl' : 'tcp';

        if ($httpTransport) {
            $prefix = $this->ssl ? 'https' : 'http';
        }

        return \sprintf(
            '%s://%s:%s@%s:%d',
            $prefix,
            $this->username,
            $maskedPassword ? '***' : $this->password,
            $this->host,
            $this->port
        );
    }
}
