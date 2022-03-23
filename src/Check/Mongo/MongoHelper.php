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

use FiveLab\Component\Diagnostic\Result\Failure;

/**
 * A simple helper for add additional functionality.
 */
class MongoHelper
{
    /**
     * @param MongoConnectionParameters $parameters
     *
     * @return array<string, string|int>
     */
    public static function convertConnectionParametersToArray(MongoConnectionParameters $parameters): array
    {
        return [
            'host' => $parameters->getHost(),
            'port' => $parameters->getPort(),
            'user' => $parameters->getUsername(),
            'pass' => '***',
            'db' => $parameters->getDb(),
            'ssl'  => $parameters->isSsl() ? 'yes' : 'no',
        ];
    }
}
