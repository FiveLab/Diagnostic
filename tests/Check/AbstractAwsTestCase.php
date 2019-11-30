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

namespace FiveLab\Component\Diagnostic\Tests\Check;

use PHPUnit\Framework\TestCase;

abstract class AbstractAwsTestCase extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function getAwsDynamodbEndpoint(): string
    {
        return \getenv('AWS_DYNAMODB_ENDPOINT') ?: '';
    }
}
