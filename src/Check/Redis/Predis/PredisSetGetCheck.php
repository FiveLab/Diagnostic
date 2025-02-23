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

namespace FiveLab\Component\Diagnostic\Check\Redis\Predis;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use Predis\Client;

readonly class PredisSetGetCheck implements CheckInterface
{
    private const PREFIX = '__diagnostic__';

    public function __construct(private string $host, private int $port, private ?string $password = null)
    {
    }

    public function check(): Result
    {
        if (!\class_exists(Client::class)) {
            return new Failure('The predis/predis not installed.');
        }

        $parameters = [
            'host' => $this->host,
            'port' => $this->port,
        ];

        if ($this->password) {
            $parameters['password'] = $this->password;
        }

        try {
            $client = new Client($parameters);

            $key = \sprintf('%s:%s', self::PREFIX, \md5(\uniqid((string) \random_int(0, PHP_INT_MAX), true)));

            // We must execute first check on try/catch because the connection maybe open only on first operation.
            $client->set($key, 'value');
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Cannot connect to Redis: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        if ('value' !== $client->get($key)) {
            return new Failure('Fail set or get the key. Writes correct value but get different value.');
        }

        $client->del([$key]);

        return new Success('Success connect to Redis and SET/GET from Redis.');
    }

    public function getExtraParameters(): array
    {
        // By security we not return password (because many redis instances work in internal network).
        return [
            'host' => $this->host,
            'port' => $this->port,
        ];
    }
}
