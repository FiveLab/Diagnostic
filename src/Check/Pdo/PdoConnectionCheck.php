<?php /** @noinspection ALL */

/*
 * This file is part of the FiveLab Diagnostic package.
 *
 * (c) FiveLab <mail@fivelab.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Pdo;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check the connect to database
 */
class PdoConnectionCheck implements CheckInterface
{
    /**
     * @var string
     */
    private string $driver;

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
    private string $dbName;

    /**
     * @var string
     */
    private string $user;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var array
     */
    private array $options;

    /**
     * Constructor.
     *
     * @param string $driver
     * @param string $host
     * @param int    $port
     * @param string $dbName
     * @param string $user
     * @param string $password
     * @param array  $options
     */
    public function __construct(string $driver, string $host, int $port, string $dbName, string $user, string $password, array $options = [])
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->dbName = $dbName;
        $this->user = $user;
        $this->password = $password;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        if (!\class_exists(\PDO::class)) {
            return new Failure('Can\'t check connect to database via PDO. PDO is not installed.');
        }

        $availableDrivers = \PDO::getAvailableDrivers();

        if (!\in_array($this->driver, $availableDrivers, true)) {
            return new Failure(\sprintf(
                'Can\'t check connect to database via PDO by driver "%s". The driver "%s" is not supported. Available drivers are "%s".',
                $this->driver,
                $this->driver,
                \implode('", "', $availableDrivers)
            ));
        }

        $dsnParts = [
            'host='.$this->host,
            'port='.$this->port,
            'dbname='.$this->dbName,
        ];

        $dsn = \sprintf('%s:%s', $this->driver, \implode(';', $dsnParts));

        try {
            $pdo = new \PDO($dsn, $this->user, $this->password, $this->options);
        } catch (\PDOException $e) {
            return new Failure('Fail connect to database. Error: '.$e->getMessage());
        }

        try {
            $stmt = $pdo->prepare('SELECT 1');
            $stmt->execute();
        } catch (\PDOException $e) {
            return new Failure('Fail execute SELECT. Error: '.$e->getMessage());
        }

        return new Success('Success connect to database.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'driver' => $this->driver,
            'host'   => $this->host,
            'port'   => $this->port,
            'dbname' => $this->dbName,
            'user'   => $this->user,
            'pass'   => '***',
        ];
    }
}
