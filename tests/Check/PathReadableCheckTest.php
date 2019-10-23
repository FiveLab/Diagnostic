<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Tests\Check;

use FiveLab\Component\Diagnostic\Check\PathReadableCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use PHPUnit\Framework\TestCase;

class PathReadableCheckTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new PathReadableCheck('/var/some.log');

        self::assertEquals([
            'path' => '/var/some.log',
        ], $check->getExtraParameters());
    }

    /**
     * @test
     */
    public function shouldSuccessIfFileIsReadable(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        \touch($path);

        $check = new PathReadableCheck($path);

        $result = $check->check();

        self::assertEquals(new Success('The file is readable.'), $result);

        \unlink($path);
    }

    /**
     * @test
     */
    public function shouldSuccessIfDirectoryIsReadable(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        \mkdir($path);

        $check = new PathReadableCheck($path);

        $result = $check->check();

        self::assertEquals(new Success('The directory is readable.'), $result);

        \rmdir($path);
    }

    /**
     * @test
     */
    public function shouldFailIfPathNotExist(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        $check = new PathReadableCheck($path);

        $result = $check->check();

        self::assertEquals(new Failure('The path not exist.'), $result);
    }

    /**
     * @test
     */
    public function shouldWarningIfPathNotExistAndDisableStrictMode(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        $check = new PathReadableCheck($path, false);

        $result = $check->check();

        self::assertEquals(new Warning('The path not exist.'), $result);
    }
}
