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
     * @var bool
     */
    private bool $ssl = false;

    /**
     * @param string $host
     * @param int    $port
     * @param string $username
     * @param string $password
     * @param string $db
     * @param bool   $ssl
     */
    public function __construct(string $host, int $port, string $username, string $password, string $db, bool $ssl = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->ssl = $ssl;
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        $userPass = \sprintf('%s:%s@', $this->username, $this->password);

        return \sprintf(
            '%s://%s%s:%s',
            $this->ssl ? 'mongodb+srv' : 'mongodb',
            $userPass,
            $this->host,
            $this->port
        );
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
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }
}
