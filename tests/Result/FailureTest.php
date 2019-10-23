<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Result;

use FiveLab\Component\Diagnostic\Result\Failure;
use PHPUnit\Framework\TestCase;

class FailureTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $result = new Failure('some foo');

        self::assertEquals('some foo', $result->getMessage());
    }
}
