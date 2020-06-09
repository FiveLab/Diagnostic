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

/**
 * The parameters for connect to RabbitMQ.
 */
class RabbitMqConnectionParameters
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $vhost;

    /**
     * @var bool
     */
    private $ssl;

    /**
     * Constructor.
     *
     * @param string $host
     * @param int    $port
     * @param string $username
     * @param string $password
     * @param string $vhost
     * @param bool   $ssl
     */
    public function __construct(string $host, int $port, string $username, string $password, string $vhost = '/', bool $ssl = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->ssl = $ssl;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get port
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Get virtual host
     *
     * @return string
     */
    public function getVhost(): string
    {
        return $this->vhost;
    }

    /**
     * Is use SSL?
     *
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }

    /**
     * Get DSN
     *
     * @param bool $httpTransport
     * @param bool $maskedPassword
     *
     * @return string
     */
    public function getDsn($httpTransport, bool $maskedPassword): string
    {
        $prefix = $this->isSsl() ? 'ssl' : 'tcp';

        if ($httpTransport) {
            $prefix = $this->isSsl() ? 'https' : 'http';
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
