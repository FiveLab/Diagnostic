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
class MongoConnectionParameters
{
    /**
     * @var string
     */
    private string $protocol;

    /**
     * @var string
     */
    private string $host;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var string
     */
    private string $username;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var string
     */
    private string $db;

    /**
     * @var array<string,int|bool|string>
     */
    private array $options;

    /**
     * @param string $protocol
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $db
     * @param array<string,int|bool|string> $options
     */
    public function __construct(string $protocol, string $host, int $port, string $username, string $password, string $db, array $options = [])
    {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->options = $options;
    }

    /**
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
            $this->parseOptions(),
        );
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDb(): string
    {
        return $this->db;
    }

    /**
     * @return array<string,int|bool|string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string
     */
    private function parseOptions(): string
    {
        if (!\count($this->options)) {
            return '';
        }

        $result = '/?';

        foreach ($this->options as $k => $v) {
            if (\is_bool($v)) {
                $v = $v ? 'true' : 'false';
            } else if (\is_int($v)) {
                $v = \strval($v);
            }

            $result .= \sprintf('%s=%s', $k, $v) . '&';
        }

        return \substr($result, 0, -1);
    }
}
