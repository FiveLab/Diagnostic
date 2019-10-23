Diagnostic
==========

The library for diagnostic application.

[![Build Status](https://travis-ci.org/FiveLab/Diagnostic.svg?branch=master)](https://travis-ci.org/FiveLab/Diagnostic)

Why?
----

This library helps for developers and devops for setup any applications.

If developer correct configure all diagnostics (paths, services, etc...) the devops before run the instance with 
our application only run diagnostic and can sees all problems (if it exist).

### Primary problem:

The developer can add any parameter or service to application. As an example - add redis service for use in runtime. 
And lost provide this information before release application. The devops (or developer) release new version application 
and the system not have any problems. But, we have a major issue because the redis only used in runtime and not 
processed in deploy process.

Devops add this instance to load balancer (if exist), and in next times the application not correct work ;(

### Solution:

Before add the instance to load balancer (or processed scope), devops run diagnostic and can see all major problems.

Restrictions
------------

If you want work with this mechanism you MUST add diagnostic check to all end points. As an example:

* Are you use cache? You MUST add check for verify access to cache directory.
* Are you use logs? You MUST add check for verify access to logs directory.
* Are you use DB? You MUST add check for connect to Database.
* Are you use Redis? You MUST add check for connect to Redis. 
* Are you use any client for connect via HTTP? You MUST add check for connect to this endpoint.
* etc...

> **Attention:** the devops (infrastructure team, release manager, developer) is not magic person and cannot see all 
  required services by sky stars. 

Configure and Run
-----------------

For easy configure the diagnostic, you can use `\FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollectionBuilder`:

```php
<?php

declare(strict_types = 1);

namespace Example;

use FiveLab\Component\Diagnostic\Check\Definition\DefinitionCollectionBuilder;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\CheckDefinitionsInGroupFilter;
use FiveLab\Component\Diagnostic\Check\Definition\Filter\OrXFilter;
use FiveLab\Component\Diagnostic\Check\PathReadableCheck;
use FiveLab\Component\Diagnostic\Check\PathWritableCheck;
use FiveLab\Component\Diagnostic\Runner\Runner;

$builder = new DefinitionCollectionBuilder();

$builder->addCheck('cache_dir', new PathWritableCheck('./var/cache'), 'system_dir');
$builder->addCheck('logs_dir', new PathWritableCheck('./var/logs'), 'system_dir');

$builder->addCheck('shared_dir', new PathWritableCheck('./var/shared'), 'shared_dir');
$builder->addCheck('shared_efs', new PathReadableCheck('./var/shared/.efs'), 'shared_dir');

$builder->addCheck('tmp_dir', new PathWritableCheck('./var/tmp'), ['tmp_dir', 'system_dir']);

$definitions = $builder->build();

$runner = new Runner();

// Run all checks
$success = $runner->run($definitions);

// Run only for system dirs
$definitions = $definitions->filter(new CheckDefinitionsInGroupFilter('system_dir'));
$success = $runner->run($definitions);

// Run tmp dir and shared dir
$definitions = $definitions->filter(new OrXFilter(
    new CheckDefinitionsInGroupFilter('tmp_dir'),
    new CheckDefinitionsInGroupFilter('shared_dir')
));

$success = $runner->run($definitions);

```

Integrate with applications
---------------------------

### Dependency Injection

You can easy integrate this library to Symfony application, or application with DependencyInjection support.

For integrate, you can add the compiler pass to your container builder:

```php
<?php

namespace Example;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use FiveLab\Component\Diagnostic\DependencyInjection\AddDiagnosticToBuilderCheckPass;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addCompilerPass(new AddDiagnosticToBuilderCheckPass());

$containerBuilder->compile();
```

Add add check services:

```yaml
services:
    diagnostic.check.cache_dir:
        class: \FiveLab\Component\Diagnostic\Check\PathWritableCheck
        arguments: [ '%kernel.cache_dir%' ]
        tags:
            - { name: diagnostic.check }

    diagnostic.check.logs_dir:
        class: \FiveLab\Component\Diagnostic\Check\PathWritableCheck
        arguments: [ '%kernel.logs_dir%' ]
        tags:
            - { name: diagnostic.check }

```

The tag `diagnostic.check` support next attributes:

* **key** - the unique key of check (default is service name).
* **group** - the group of this check (default is null).

### Console commands

We provide console commands for easy integrate to any applications:

* `\FiveLab\Component\Diagnostic\Command\RunDiagnosticCommand` - command for run diagnostic.
* `\FiveLab\Component\Diagnostic\Command\ListChecksCommand` - command for list all available checks.
* `\FiveLab\Component\Diagnostic\Command\ListGroupsCommand` - command for list all available groups.

Development
-----------

For easy development you can use the `Docker` and `Docker compose`.

```bash
$ docker-compose up
$ docker-compose exec diagnostic bash

```

After success run and attach to container you must install vendors:

```bash
$ composer install
```

Before create the PR or merge into develop, please run next commands for validate code:

```bash
$ ./bin/phpunit

$ ./bin/phpcs --config-set show_warnings 0
$ ./bin/phpcs --standard=vendor/escapestudios/symfony2-coding-standard/Symfony/ src/
$ ./bin/phpcs --standard=tests/phpcs-ruleset.xml tests/

```
