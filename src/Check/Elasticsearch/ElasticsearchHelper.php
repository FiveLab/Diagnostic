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

use FiveLab\Component\Diagnostic\Result\Failure;

/**
 * A simple helper for add additional functionality.
 */
class ElasticsearchHelper
{
    /**
     * Convert elastic search connection parameters to array for view after check.
     *
     * @param ElasticsearchConnectionParameters $parameters
     *
     * @return array<string, string|int>
     */
    public static function convertConnectionParametersToArray(ElasticsearchConnectionParameters $parameters): array
    {
        $params = [
            'host' => $parameters->getHost(),
            'port' => $parameters->getPort(),
            'ssl'  => $parameters->isSsl() ? 'yes' : 'no',
        ];

        if ($parameters->getUsername() || $parameters->getPassword()) {
            $params['user'] = $parameters->getUsername() ?: '(null)';
            $params['pass'] = '***';
        }

        return $params;
    }
}
