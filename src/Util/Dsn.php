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

readonly class Dsn
{
    public string $target;

    /**
     * Constructor.
     *
     * @param string                    $scheme
     * @param string                    $host
     * @param string                    $path
     * @param int|null                  $port
     * @param string|null               $username
     * @param string|null               $password
     * @param array<string, mixed>|null $options
     */
    public function __construct(
        public string  $scheme,
        public string  $host,
        public string  $path,
        public ?int    $port,
        public ?string $username = null,
        public ?string $password = null,
        public ?array  $options = null,
    ) {
        $this->target = \ltrim($this->path, '/');
    }

    /**
     * Parse URL string to DSN object.
     *
     * @param string                    $dsn
     * @param array<string, mixed>|null $defaults
     *
     * @return self
     */
    public static function parse(string $dsn, ?array $defaults = null): self
    {
        $parts = \parse_url($dsn);

        if (!$parts) {
            throw new \InvalidArgumentException(\sprintf(
                'Can\'t parse DSN "%s".',
                $dsn
            ));
        }

        if (\array_keys($parts) === ['path'] && !\str_contains($parts['path'], '/')) {
            // Only path passed. Use path as host.
            $parts = ['host' => $parts['path']];
        }

        if (!$scheme = $parts['scheme'] ?? $defaults['scheme'] ?? null) {
            throw new \InvalidArgumentException(\sprintf(
                'Missed "scheme" in DSN "%s".',
                $dsn
            ));
        }

        if (!$host = $parts['host'] ?? $defaults['host'] ?? null) {
            throw new \InvalidArgumentException(\sprintf(
                'Missed "host" in DSN "%s".',
                $dsn
            ));
        }

        \parse_str($parts['query'] ?? '', $options);

        return new self(
            $scheme,
            $host,
            $parts['path'] ?? $defaults['path'] ?? '/',
            $parts['port'] ?? $defaults['port'] ?? null,
            $parts['user'] ?? $defaults['user'] ?? null,
            $parts['pass'] ?? $defaults['pass'] ?? null,
            $options ?: ($defaults['options'] ?? null)
        );
    }

    public function getDsn(bool $path = false, bool $query = false, bool $maskedPassword = false): string
    {
        $userPass = '';

        if ($this->username || $this->password) {
            $userPass = \sprintf('%s:%s@', $this->username, $maskedPassword ? '***' : $this->password);
        }

        return \sprintf(
            '%s://%s%s%s%s%s',
            $this->scheme,
            $userPass,
            $this->host,
            $this->port ? ':'.$this->port : '',
            $path ? '/'.\ltrim($this->path, '/') : '',
            $query ? ($this->options ? '?'.\http_build_query($this->options) : '') : '',
        );
    }
}
