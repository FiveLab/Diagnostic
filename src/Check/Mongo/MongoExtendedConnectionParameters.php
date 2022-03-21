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
 * Extended model that stores additional connection parameters for MongoDB.
 */
class MongoExtendedConnectionParameters
{
    /**
     * @param string                    $username
     * @param string                    $password
     * @param string                    $db
     * @param string                    $collection
     * @param MongoConnectionParameters $connectionParameters
     */
    public function __construct(private string $username, private string $password, private string $db, private string $collection, public MongoConnectionParameters $connectionParameters)
    {
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        $userPass = \sprintf('%s:%s@', $this->username, $this->password);

        return \sprintf(
            '%s/%s',
            \str_replace('://', '://'.$userPass, $this->connectionParameters->getDsn()),
            $this->getDb()
        );
    }

    /**
     * @return string
     */
    public function getDb(): string
    {
        return $this->db;
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
    public function getCollection(): string
    {
        return $this->collection;
    }
}
