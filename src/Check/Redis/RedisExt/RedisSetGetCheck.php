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
use FiveLab\Component\Diagnostic\Util\Dsn;

readonly class RedisSetGetCheck implements CheckInterface
{
    private const PREFIX = '__diagnostic__';

    public function __construct(private Dsn $dsn)
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
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Cannot connect to Redis: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        } finally {
            \restore_error_handler();
        }

        $key = \sprintf('%s:%s', self::PREFIX, \md5(\uniqid((string) \random_int(0, PHP_INT_MAX), true)));

        $redis->set($key, 'value');

        if ('value' !== $redis->get($key)) {
            return new Failure('Fail set or get the key. Writes correct value but get different value.');
        }

        $redis->del($key);

        return new Success('Success connect to Redis and SET/GET from Redis.');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn' => $this->dsn->getDsn(path: true, maskedPassword: true),
        ];
    }

    private function connect(\Redis $redis): void
    {
        $redis->connect($this->dsn->host, $this->dsn->port ?: 6379);

        if ($this->dsn->password) {
            $redis->auth($this->dsn->password);
        }

        $redis->select((int) $this->dsn->target);
    }
}
