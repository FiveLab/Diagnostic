#!/usr/bin/env php
<?php

declare(strict_types = 1);

$expectedPackageName = $argv[1] ?? null;
$expectedPackageVersion = $argv[2] ?? null;

if (!$expectedPackageName || !$expectedPackageVersion) {
    print 'The package or version not specified.'.PHP_EOL;
    exit (1);
}

$composerFile = __DIR__.'/../composer.json';
$composerData = \json_decode(\file_get_contents($composerFile), true);

$updated = false;

foreach ($composerData['require'] as $name => $version) {
    if (0 === \strpos($name, $expectedPackageName)) {
        $composerData['require'][$name] = $expectedPackageVersion;
        $updated = true;
    }
}

foreach ($composerData['require-dev'] as $name => $version) {
    if (0 === \strpos($name, $expectedPackageName)) {
        $composerData['require-dev'][$name] = $expectedPackageVersion;
        $updated = true;
    }
}

if (!$updated) {
    print \sprintf('Nothing to update by pattern "%s".', $expectedPackageVersion);
    exit (1);
}

\file_put_contents($composerFile, \json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));