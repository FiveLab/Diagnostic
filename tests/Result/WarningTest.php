<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Result;

use FiveLab\Component\Diagnostic\Result\Warning;
use PHPUnit\Framework\TestCase;

class WarningTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessCreate(): void
    {
        $result = new Warning('some foo');

        self::assertEquals('some foo', $result->getMessage());
    }
}
