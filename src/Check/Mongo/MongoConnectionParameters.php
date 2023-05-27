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

namespace FiveLab\Component\Diagnostic\Check\Mongo;

/**
 * Model that stores connection parameters for MongoDB.
 */
readonly class MongoConnectionParameters
{
    /**
     * Constructor.
     *
     * @param string               $protocol
     * @param string               $host
     * @param int                  $port
     * @param string               $username
     * @param string               $password
     * @param string               $db
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $protocol,
        public string $host,
        public int    $port,
        public string $username,
        public string $password,
        public string $db,
        public array  $options = []
    ) {
    }

    /**
     * Format DSN for connect
     *
     * @return string
     */
    public function getDsn(): string
    {
        $userPass = \sprintf('%s:%s@', $this->username, $this->password);

        return \sprintf(
            '%s://%s%s:%s%s',
            $this->protocol,
            $userPass,
            $this->host,
            $this->port,
            $this->formatOptions(),
        );
    }

    /**
     * Format options to string
     *
     * @return string
     */
    private function formatOptions(): string
    {
        if (!\count($this->options)) {
            return '';
        }

        $options = \array_map(static function ($v) {
            if (\is_bool($v)) {
                return $v ? 'true' : 'false';
            }

            return $v;
        }, $this->options);

        return '/?'.\http_build_query($options);
    }
}
