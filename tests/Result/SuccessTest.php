<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Result;

use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\TestCase;

class SuccessTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $result = new Success('some foo');

        self::assertEquals('some foo', $result->getMessage());
    }
}
