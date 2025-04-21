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

namespace FiveLab\Component\Diagnostic\Check\Aws;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;

class AwsCaPublicKeyCheck implements CheckInterface
{
    private ?string $actualFingerprint = null;
    private ?string $expectedFingerprint = null;

    public function __construct(private readonly string $publicKeyPath)
    {
    }

    public function check(): Result
    {
        if (!\file_exists($this->publicKeyPath)) {
            return new Failure('The PEM file does not exist.');
        }

        $actualFingerprint = $this->generateFingerprint($this->publicKeyPath);

        if ($actualFingerprint instanceof Result) {
            return $actualFingerprint;
        }

        $expectedFingerprint = $this->generateFingerprint('https://www.amazontrust.com/repository/AmazonRootCA1.pem');

        if ($expectedFingerprint instanceof Result) {
            return $expectedFingerprint;
        }

        $this->actualFingerprint = $actualFingerprint;
        $this->expectedFingerprint = $expectedFingerprint;

        if ($this->actualFingerprint !== $this->expectedFingerprint) {
            return new Failure('The fingerprints do not match.');
        }

        return new Success('Success check AWS CA public key.');
    }

    public function getExtraParameters(): array
    {
        return [
            'aws ca public key'    => $this->publicKeyPath,
            'expected fingerprint' => $this->expectedFingerprint ?: '(null)',
            'actual fingerprint'   => $this->actualFingerprint ?: '(null)',
        ];
    }

    private function generateFingerprint(string $path): Result|string
    {
        $key = \openssl_pkey_get_public(\file_get_contents($path));

        if (false === $key) {
            return new Failure(\sprintf(
                'Fail getting MD5 signature for PEM (%s).',
                $path
            ));
        }

        $details = \openssl_pkey_get_details($key);
        $der = \preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', $details['key']);
        $der = \base64_decode($der, true);

        $md5 = \md5($der);

        return \implode(':', \str_split(\strtoupper($md5), 2));
    }
}
