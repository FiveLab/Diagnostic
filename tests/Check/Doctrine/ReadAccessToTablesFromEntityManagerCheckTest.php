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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\Mapping\ClassMetadata;
use FiveLab\Component\Diagnostic\Check\Doctrine\ReadAccessToTablesFromEntityManagerCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\Doctrine\Entity\User;
use PHPUnit\Framework\Attributes\Test;

class ReadAccessToTablesFromEntityManagerCheckTest extends AbstractDoctrineCheckTestCase
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

    #[Test]
    public function shouldPassCheckForExistingTables(): void
    {
        $this->createSchema();

        $check = new ReadAccessToTablesFromEntityManagerCheck($this->entityManager);
        $result = $check->check();

        self::assertEquals(new Success('Success check rights for read from all tables in entity manager.'), $result);
    }

    #[Test]
    public function shouldFailCheckWithEmptySchema(): void
    {
        $check = new ReadAccessToTablesFromEntityManagerCheck($this->entityManager);
        $result = $check->check();

        self::assertEquals(new Failure('Fail check read access from tables: "products", "users".'), $result);
    }

    #[Test]
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

    #[Test]
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
     * Setup database
     */
    private function setUpDatabase(): void
    {
        $connection = $this->makeDbalConnection();
        $isDevMode = true;  // Creates doctrine cache as a php array

        $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/Entity'], $isDevMode);

        $this->entityManager = EntityManager::create($connection, $config);
        $this->schemaTool = new SchemaTool($this->entityManager);
    }

    /**
     * Create schema
     */
    private function createSchema(): void
    {
        $this->schemaTool->createSchema($this->getEntityMetadata());
    }

    /**
     * Drop schema
     */
    private function dropSchema(): void
    {
        $this->schemaTool->dropSchema($this->getEntityMetadata());
    }

    /**
     * Drop table by entity class.
     *
     * @param string $entityClass
     */
    private function dropEntityClassTable(string $entityClass): void
    {
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);
        $this->schemaTool->dropSchema([$classMetadata]);
    }

    /**
     * Get doctrine metadata.
     *
     * @return ClassMetadata[]|\Doctrine\Common\Persistence\Mapping\ClassMetadata[]
     */
    private function getEntityMetadata(): array
    {
        return $this->entityManager->getMetadataFactory()->getAllMetadata();
    }
}
