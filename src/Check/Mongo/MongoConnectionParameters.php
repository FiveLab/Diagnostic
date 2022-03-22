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
    private int $port = 27017;

    /**
     * @var bool
     */
    private bool $ssl = false;

    /**
     * @param string $host
     * @param int    $port
     * @param bool   $ssl
     */
    public function __construct(string $host, int $port = 27017, bool $ssl = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->ssl = $ssl;
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        return \sprintf(
            '%s://%s:%s',
            $this->ssl ? 'mongodb+srv' : 'mongodb',
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
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }
}
