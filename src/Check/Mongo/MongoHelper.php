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
            'ssl'  => $parameters->isSsl() ? 'yes' : 'no',
        ];
    }

    /**
     * @param MongoExtendedConnectionParameters $parameters
     *
     * @return array<string, string|int>
     */
    public static function convertExtendedConnectionParametersToArray(MongoExtendedConnectionParameters $parameters): array
    {
        return \array_merge(
            self::convertConnectionParametersToArray($parameters->connectionParameters),
            [
                'user' => $parameters->getUsername(),
                'pass' => '***',
                'db' => $parameters->getDb(),
            ]
        );
    }

    /**
     * Try to get specific setting from settings
     *
     * @param string $path
     * @param array  $settings
     *
     * @return Failure|string
     */
    public static function tryGetSpecificSettingFromSettings(string $path, array $settings)
    {
        $pathParts = \explode('.', $path);

        $processedPath = '';

        while ($pathPart = \array_shift($pathParts)) {
            $processedPath .= $pathPart.'.';

            if (!\array_key_exists($pathPart, $settings)) {
                return new Failure(\sprintf(
                    'The setting "%s" is missed.',
                    \rtrim($processedPath, '.')
                ));
            }

            if (\count($pathParts)) {
                // Not last element. Get inner array.
                $settings = $settings[$pathPart];
            } else {
                // Last element. Get value.
                return $settings[$pathPart];
            }
        }

        return new Failure(\sprintf('Cannot get setting by path: "%s".', $path));
    }
}
