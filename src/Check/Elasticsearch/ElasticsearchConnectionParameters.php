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

/**
 * The model for store parameters for connect to Elasticsearch.
 */
class ElasticsearchConnectionParameters
{
    /**
     * @var string
     */
    private string $host;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var string|null
     */
    private ?string $username;

    /**
     * @var string|null
     */
    private ?string $password;

    /**
     * @var bool
     */
    private bool $ssl;

    /**
     * Constructor.
     *
     * @param string      $host
     * @param int         $port
     * @param string|null $username
     * @param string|null $password
     * @param bool        $ssl
     */
    public function __construct(string $host, int $port = 9200, string $username = null, string $password = null, bool $ssl = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->ssl = $ssl;
    }

    /**
     * Get DSN
     *
     * @return string
     */
    public function getDsn(): string
    {
        $userPass = '';

        if ($this->username) {
            $userPass = \sprintf('%s:%s@', $this->username, $this->password);
        }

        return \sprintf(
            '%s://%s%s:%s',
            $this->ssl ? 'https' : 'http',
            $userPass,
            $this->host,
            $this->port
        );
    }

    /**
     * Get the host for connect to Elasticsearch
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the port for connect to Elasticsearch
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get the username for connect to Elasticsearch
     *
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Get the password for connect to Elasticsearch
     *
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Is must use SSL connection?
     *
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }
}
