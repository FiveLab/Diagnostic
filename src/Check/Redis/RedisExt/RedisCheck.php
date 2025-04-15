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

namespace FiveLab\Component\Diagnostic\Check\Redis\RedisExt;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

readonly class RedisCheck implements CheckInterface
{
    private const PREFIX = '__diagnostic__';

    public function __construct(private string $host, private int $port, private ?string $password = null)
    {
    }

    public function check(): Result
    {
        if (!\class_exists(\Redis::class)) {
            return new Failure('The ext-redis not installed.');
        }

        $redis = new \Redis();

        \set_error_handler(static function (int $errno, string $errstr) {
            throw new \Exception($errstr);
        });

        try {
            $this->connect($redis);
            $redis->ping();
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Cannot connect to Redis: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        } finally {
            \restore_error_handler();
        }

        return new Success('Success connect to Redis.');
    }

    public function getExtraParameters(): array
    {
        // By security we not return password (because many redis instances work in internal network).
        return [
            'host' => $this->host,
            'port' => $this->port,
        ];
    }

    private function connect(\Redis $redis): void
    {
        $redis->connect($this->host, $this->port);

        if ($this->password) {
            $redis->auth($this->password);
        }
    }
}
