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

namespace FiveLab\Component\Diagnostic\Check\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

class ReadAccessToTablesFromEntityManagerCheck implements CheckInterface
{
    /**
     * @var array<string>
     */
    private array $tables = [];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function check(): Result
    {
        $this->tables = $this->getTableNames();
        $failureSelect = [];

        foreach ($this->tables as $tableName) {
            $readAccess = $this->isSelectSuccessful($tableName);

            if (!$readAccess) {
                $failureSelect[] = $tableName;
            }
        }

        if (\count($failureSelect)) {
            return new Failure(\sprintf(
                'Fail check read access from tables: "%s".',
                \implode('", "', $failureSelect)
            ));
        }

        return new Success('Success check rights for read from all tables in entity manager.');
    }

    public function getExtraParameters(): array
    {
        return [
            'dbname' => $this->entityManager->getConnection()->getDatabase(),
            'tables' => \implode(', ', $this->tables),
        ];
    }

    /**
     * Get name of tables
     *
     * @return array<string>
     */
    private function getTableNames(): array
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $tableNames = \array_map(function (ClassMetadata $metadata) {
            return $metadata->getTableName();
        }, $metadata);

        \sort($tableNames);

        return $tableNames;
    }

    private function isSelectSuccessful(string $tableName): bool
    {
        $connection = $this->entityManager->getConnection();

        $stmt = $connection->prepare(\sprintf('SELECT 1 FROM %s LIMIT 1', $tableName));

        try {
            $stmt->executeStatement();
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
