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

namespace FiveLab\Component\Diagnostic\Tests\Check\Aws\DynamoDb;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Sdk;
use FiveLab\Component\Diagnostic\Check\Aws\DynamoDb\DynamoDbTableExistCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Tests\Check\AbstractAwsTestCase;

class DynamoDbTableExistCheckTest extends AbstractAwsTestCase
{
    /**
     * @var DynamoDbClient
     */
    private DynamoDbClient $dynamodb;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!$this->getAwsDynamodbEndpoint()) {
            self::markTestSkipped('The AWS DynamoDB is not configured');
        }

        $sdk = $this->createSdk();

        $this->dynamodb = $sdk->createDynamoDb();

        $this->dynamodb->createTable([
            'TableName'             => 'test',
            'AttributeDefinitions'  => [
                ['AttributeName' => 'id', 'AttributeType' => 'S'],
            ],
            'KeySchema'             => [
                ['AttributeName' => 'id', 'KeyType' => 'HASH'],
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits'  => 5,
                'WriteCapacityUnits' => 5,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->dynamodb->deleteTable([
            'TableName' => 'test',
        ]);
    }

    /**
     * @test
     */
    public function shouldSuccessCheck(): void
    {
        $check = new DynamoDbTableExistCheck($this->createSdk(), 'test', $this->getAwsDynamodbEndpoint());

        $result = $check->check();

        self::assertEquals(new Success('The table exist in DynamoDB.'), $result);
    }

    /**
     * @test
     */
    public function shouldFailCheckIfTableNotFound(): void
    {
        $check = new DynamoDbTableExistCheck($this->createSdk(), 'some', $this->getAwsDynamodbEndpoint());

        $result = $check->check();

        self::assertEquals(new Failure('The table was not found in DynamoDB.'), $result);
    }

    /**
     * @test
     */
    public function shouldSuccessGetExtraParams(): void
    {
        $check = new DynamoDbTableExistCheck($this->createSdk(), 'foo-bar', $this->getAwsDynamodbEndpoint());

        self::assertEquals([
            'table' => 'foo-bar',
        ], $check->getExtraParameters());
    }

    /**
     * Create a SDK
     *
     * @return Sdk
     */
    private function createSdk(): Sdk
    {
        return new Sdk([
            'credentials' => [
                'key'    => 'local',
                'secret' => 'local',
            ],
            'region'      => 'local',
            'endpoint'    => $this->getAwsDynamodbEndpoint(),
            'version'     => 'latest',
        ]);
    }
}
