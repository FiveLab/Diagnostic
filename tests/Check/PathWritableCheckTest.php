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

use FiveLab\Component\Diagnostic\Check\PathWritableCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Result\Warning;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PathWritableCheckTest extends TestCase
{
    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $check = new PathWritableCheck('/var/some.log');

        self::assertEquals([
            'path' => '/var/some.log',
        ], $check->getExtraParameters());
    }

    #[Test]
    public function shouldSuccessIfFileIsReadable(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        \touch($path);

        $check = new PathWritableCheck($path);

        $result = $check->check();

        self::assertEquals(new Success('The file is writable.'), $result);

        \unlink($path);
    }

    #[Test]
    public function shouldSuccessIfDirectoryIsReadable(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        \mkdir($path);

        $check = new PathWritableCheck($path);

        $result = $check->check();

        self::assertEquals(new Success('The directory is writable.'), $result);

        \rmdir($path);
    }

    #[Test]
    public function shouldFailIfPathNotExist(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        $check = new PathWritableCheck($path);

        $result = $check->check();

        self::assertEquals(new Failure('The path not exist.'), $result);
    }

    #[Test]
    public function shouldWarningIfPathNotExistAndDisableStrictMode(): void
    {
        $path = \sys_get_temp_dir().'/'.\md5(\uniqid());

        $check = new PathWritableCheck($path, false);

        $result = $check->check();

        self::assertEquals(new Warning('The path not exist.'), $result);
    }
}
