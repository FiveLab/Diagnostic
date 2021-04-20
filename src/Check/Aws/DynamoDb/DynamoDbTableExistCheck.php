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

namespace FiveLab\Component\Diagnostic\Check\Aws\DynamoDb;

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Sdk;
use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\ResultInterface;
use FiveLab\Component\Diagnostic\Result\Success;

/**
 * Check what the table exist in DynamoDB (AWS).
 */
class DynamoDbTableExistCheck implements CheckInterface
{
    /**
     * @var Sdk
     */
    private Sdk $sdk;

    /**
     * @var string
     */
    private string $tableName;

    /**
     * @var string
     */
    private string $endpoint;

    /**
     * Constructor.
     *
     * @param Sdk    $sdk
     * @param string $tableName
     * @param string $endpoint
     */
    public function __construct(Sdk $sdk, string $tableName, string $endpoint)
    {
        $this->sdk = $sdk;
        $this->tableName = $tableName;
        $this->endpoint = $endpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function check(): ResultInterface
    {
        $dynamodb = $this->sdk->createDynamoDb([
            'endpoint' => $this->endpoint,
        ]);

        try {
            $listTablesResponse = $dynamodb->listTables();
        } catch (DynamoDbException $e) {
            return new Failure('Fail check table exist. Error: '.($e->getAwsErrorMessage() ?: $e->getMessage()));
        }

        $tableNames = $listTablesResponse->get('TableNames');

        if (!\in_array($this->tableName, $tableNames, true)) {
            return new Failure('The table was not found in DynamoDB.');
        }

        return new Success('The table exist in DynamoDB.');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraParameters(): array
    {
        return [
            'table' => $this->tableName,
        ];
    }
}
