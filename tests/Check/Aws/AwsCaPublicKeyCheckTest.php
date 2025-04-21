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

namespace FiveLab\Component\Diagnostic\Tests\Check\Aws;

use FiveLab\Component\Diagnostic\Check\Aws\AwsCaPublicKeyCheck;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Success;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AwsCaPublicKeyCheckTest extends TestCase
{
    private ?string $fileForDelete = null;

    protected function tearDown(): void
    {
        if ($this->fileForDelete) {
            @\unlink($this->fileForDelete);
        }
    }

    #[Test]
    public function shouldSuccessCheck(): void
    {
        $actualKey = $this->downloadTemporaryFile('https://www.amazontrust.com/repository/AmazonRootCA1.pem');

        $check = new AwsCaPublicKeyCheck($actualKey);
        $result = $check->check();

        self::assertEquals(new Success('Success check AWS CA public key.'), $result);
    }

    #[Test]
    public function shouldSuccessGetExtraParameters(): void
    {
        $actualKey = $this->downloadTemporaryFile('https://www.amazontrust.com/repository/AmazonRootCA1.pem');

        $check = new AwsCaPublicKeyCheck($actualKey);
        $check->check();

        $extraParameters = $check->getExtraParameters();

        self::assertCount(3, $extraParameters);

        self::assertEquals($this->fileForDelete, $extraParameters['aws ca public key']);
        self::assertEquals($extraParameters['actual fingerprint'], $extraParameters['expected fingerprint']);
    }

    #[Test]
    public function shouldFailCheck(): void
    {
        $resource = \openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);

        $details = \openssl_pkey_get_details($resource);

        $key = $this->downloadTemporaryFile($details['key']);

        $check = new AwsCaPublicKeyCheck($key);
        $result = $check->check();

        self::assertEquals(new Failure('The fingerprints do not match.'), $result);
    }

    #[Test]
    public function shouldFailIfKeyIsInvalid(): void
    {
        $key = $this->downloadTemporaryFile('bla-bla');

        $check = new AwsCaPublicKeyCheck($key);
        $result = $check->check();

        self::assertEquals(new Failure(\sprintf('Fail getting MD5 signature for PEM (%s).', $this->fileForDelete)), $result);
    }

    #[Test]
    public function shouldFailIfKeyIsMissed(): void
    {
        $check = new AwsCaPublicKeyCheck('bla-bla.pem');
        $result = $check->check();

        self::assertEquals(new Failure('The PEM file does not exist.'), $result);
    }

    private function downloadTemporaryFile(string $url): string
    {
        $tmpname = \tempnam(\sys_get_temp_dir(), 'aws-ca');

        if (\str_starts_with($url, 'https://')) {
            $content = \file_get_contents($url);
        } else {
            $content = $url;
        }

        \file_put_contents($tmpname, $content);

        $this->fileForDelete = $tmpname;

        return $tmpname;
    }
}
