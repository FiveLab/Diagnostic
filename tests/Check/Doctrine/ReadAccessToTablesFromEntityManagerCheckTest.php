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

namespace FiveLab\Component\Diagnostic\Tests\Check\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\Mapping\ClassMetadata;
use FiveLab\Component\Diagnostic\Check\Doctrine\ReadAccessToTablesFromEntityManagerCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractDatabaseTestCase;
use FiveLab\Component\Diagnostic\Tests\Check\Doctrine\Entity\User;

class ReadAccessToTablesFromEntityManagerCheckTest extends AbstractDatabaseTestCase
{
    /**
     * @var EntityManager
     */
    private EntityManager $entityManager;

    /**
     * @var SchemaTool
     */
    private SchemaTool $schemaTool;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->canTestingWithDatabase()) {
            self::markTestSkipped('The database not configured.');
        }

        $this->setUpDatabase();
        $this->dropSchema();
    }

    /**
     * @test
     */
    public function shouldPassCheckForExistingTables(): void
    {
        $this->createSchema();

        $check = new ReadAccessToTablesFromEntityManagerCheck($this->entityManager);
        $result = $check->check();

        self::assertEquals(new Success('Success check rights for read from all tables in entity manager.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckWithEmptySchema(): void
    {
        $check = new ReadAccessToTablesFromEntityManagerCheck($this->entityManager);
        $result = $check->check();

        self::assertEquals(new Failure('Fail check read access from tables: "products", "users".'), $result);
    }

    /**
     * @test
     */
    public function shouldReturnCorrectExtraParameters(): void
    {
        $this->createSchema();
        $this->dropEntityClassTable(User::class);

        $check = new ReadAccessToTablesFromEntityManagerCheck($this->entityManager);
        $check->check();

        $expected = [
            'dbname' => 'diagnostic',
            'tables' => 'products, users',
        ];

        self::assertSame($expected, $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldSkipSuccessfully(): void
    {
        $check = new ReadAccessToTablesFromEntityManagerCheck($this->entityManager);

        // Not running $check->check()

        $expected = [
            'dbname' => 'diagnostic',
            'tables' => '',
        ];

        self::assertSame($expected, $check->getExtraParameters());
    }

    /**
     * @return void
     */
    private function setUpDatabase(): void
    {
        $connection = new Connection($this->getConnectionOptions(), new Driver());
        $isDevMode = true;  // Creates doctrine cache as a php array

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__."/Entity"], $isDevMode);

        $this->entityManager = EntityManager::create($connection, $config);
        $this->schemaTool = new SchemaTool($this->entityManager);
    }

    /**
     * return @void
     */
    private function createSchema(): void
    {
        $this->schemaTool->createSchema($this->getEntityMetadata());
    }

    /**
     * return @void
     */
    private function dropSchema(): void
    {
        $this->schemaTool->dropSchema($this->getEntityMetadata());
    }

    /**
     * @param string $entityClass
     *
     * return @void
     */
    private function dropEntityClassTable(string $entityClass): void
    {
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);
        $this->schemaTool->dropSchema([$classMetadata]);
    }

    /**
     * @return ClassMetadata[]
     */
    private function getEntityMetadata(): array
    {
        return $this->entityManager->getMetadataFactory()->getAllMetadata();
    }

    /**
     * Get connection options
     *
     * @return array
     */
    private function getConnectionOptions(): array
    {
        return [
            'host'     => $this->getDatabaseHost(),
            'port'     => $this->getDatabasePort(),
            'dbname'   => $this->getDatabaseName(),
            'user'     => $this->getDatabaseUser(),
            'password' => $this->getDatabasePassword(),
        ];
    }
}
