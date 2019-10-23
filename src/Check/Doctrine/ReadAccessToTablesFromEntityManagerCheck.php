<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Check\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Class whether database tables of registered EntityManager entities are accessible
 */
class ReadAccessToTablesFromEntityManagerCheck implements CheckInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var array|string[]
     */
    private $tables = [];

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    public function check(): ResultInterface
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

    /**
     * {@inheritDoc}
     */
    public function getExtraParameters(): array
    {
        $parameters = [
            'dbname' => $this->entityManager->getConnection()->getDatabase(),
            'tables' => \implode(', ', $this->tables),
        ];

        return $parameters;
    }

    /**
     * Get name of tables
     *
     * @return array
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

    /**
     * Is we have access to read from table?
     *
     * @param string $tableName
     *
     * @return bool
     */
    private function isSelectSuccessful(string $tableName): bool
    {
        $connection = $this->entityManager->getConnection();

        $stmt = $connection->prepare(\sprintf('SELECT 1 FROM %s LIMIT 1', $tableName));

        try {
            $stmt->execute();
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}
