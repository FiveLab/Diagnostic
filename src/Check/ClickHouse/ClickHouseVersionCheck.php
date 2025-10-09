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

namespace FiveLab\Component\Diagnostic\Check\ClickHouse;

use FiveLab\Component\Diagnostic\Check\CheckInterface;
use FiveLab\Component\Diagnostic\Result\Failure;
use FiveLab\Component\Diagnostic\Result\Result;
use FiveLab\Component\Diagnostic\Result\Success;
use FiveLab\Component\Diagnostic\Util\Dsn;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapter;
use FiveLab\Component\Diagnostic\Util\Http\HttpAdapterInterface;
use FiveLab\Component\Diagnostic\Util\VersionComparator\SemverVersionComparator;
use FiveLab\Component\Diagnostic\Util\VersionComparator\VersionComparatorInterface;

class ClickHouseVersionCheck implements CheckInterface
{
    use ClickHouseHelperTrait;

    private HttpAdapterInterface $http;
    private readonly VersionComparatorInterface $versionComparator;
    private ?string $actualVersion = null;

    public function __construct(
        private readonly Dsn        $dsn,
        private readonly string     $version,
        ?VersionComparatorInterface $versionComparator = null,
        ?HttpAdapterInterface       $httpAdapter = null
    ) {
        $this->versionComparator = $versionComparator ?: new SemverVersionComparator();
        $this->http = $httpAdapter ?? new HttpAdapter();
    }

    public function check(): Result
    {
        $result = $this->sendRequest($this->http, $this->dsn, '?query=SELECT version()');

        if ($result instanceof Result) {
            return $result;
        }

        $this->actualVersion = $result;

        if (!$this->versionComparator->satisfies($this->actualVersion, $this->version)) {
            return new Failure('Fail check ClickHouse version.');
        }

        return new Success('Success check ClickHouse version.');
    }

    public function getExtraParameters(): array
    {
        return [
            'dsn'              => $this->dsn->getDsn(path: true, maskedPassword: true),
            'expected version' => $this->version,
            'actual version'   => $this->actualVersion ?: '(null)',
        ];
    }
}
