<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Redis\RedisExt;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check access to Redis and simple operation (SET/GET)
 */
class RedisSetGetCheck implements CheckInterface
{
    private const PREFIX = '__diagnostic__';

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string|null
     */
    private $password;

    /**
     * Constructor.
     *
     * @param string      $host
     * @param int         $port
     * @param string|null $password
     */
    public function __construct(string $host, int $port, string $password = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(\Redis::class)) {
            return new Failure('The ext-redis not installed.');
        }

        $redis = new \Redis();

        try {
            $this->connect($redis);
        } catch (\Throwable $e) {
            return new Failure(\sprintf(
                'Cannot connect to Redis: %s.',
                \rtrim($e->getMessage(), '.')
            ));
        }

        $key = \sprintf('%s:%s', self::PREFIX, \md5(\uniqid((string) \random_int(0, PHP_INT_MAX), true)));

        $redis->set($key, 'value');

        if ('value' !== $redis->get($key)) {
            return new Failure('Fail set or get the key. Writes correct value but get different value.');
        }

        $redis->del($key);

        return new Success('Success connect to Redis and SET/GET from Redis.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        // By security we not return password (because many redis instances work in internal network).
        return [
            'host' => $this->host,
            'port' => $this->port,
        ];
    }

    /**
     * Connect to redis
     *
     * @param \Redis $redis
     */
    private function connect(\Redis $redis): void
    {
        $redis->connect($this->host, $this->port);

        if ($this->password) {
            $redis->auth($this->password);
        }
    }
}
