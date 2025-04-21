CHANGELOG
=========

Next release
------------

Add next checks:

* `FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementVersionCheck` - check RabbitMQ version based on Management.
* `FiveLab\Component\Diagnostic\Check\Redis\RedisExt\RedisPingPongCheck` - check Redis by Ping/Pong via php extension.
* `FiveLab\Component\Diagnostic\Check\Redis\Predis\PredisPingPongCheck` - check Redis by Ping/Ping via `predis/predis` library.

v2.1.0
------

* Remove `php-http/*` packages.
* Use only psr client and factories for http checks.
* Use http client for elasticsearch/opensearch checks (instead of clients).
* Remove support Symfony 5, minimum required version 6.4

v2.0.4
------

* Add support PHP 8.4 (remove deprecations).

v2.0.3
------

* Add support Symfony 7.*.

v2.0.2
------

* Add possible to ignore failures (error on failure).

v2.0.1
------

* Fix check ENV Regex with zero value (`FOO_BAR=0` as example).

v2.0.0
------

* Require PHP 8.2 and higher.
* Remove `SwiftMailer` checks (package is deprecated, use `symfony/mailer` instead).
* Remove `FiveLab\Component\Diagnostic\Result\ResultInterface`, use `FiveLab\Component\Diagnostic\Result\Result` instead.
* Remove `FiveLab\Component\Diagnostic\Check\Http\PingableHttpCheck`, use `FiveLab\Component\Diagnostic\Check\Http\HttpCheck` instead.

v1.3.9
------

* Use lazy console commands.

v1.3.8
------

* Add `useLazyDecorator` to compiler for make lazy loader for get original check from `Psr Container`.

v1.3.7
------

* Add [OpenSearch](https://opensearch.org/) support providing ClientBuilder to all elasticsearch checks.

v1.3.6
------

* Force convert application version to string in `PingableHttpCheck` (app can return version in integer/float, `1.0` as an example).

v1.3.5
------

* Throw error if try to create definition collection with same keys.

v1.3.4
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\Mongo\MongoCollectionCheck` - check collection existence and required parameters.
* `\FiveLab\Component\Diagnostic\Check\Mongo\MongoConnectionCheck` - check connection to MongoDB.

v1.3.3
------

* Add `codes` argument for Symfony Mailer SMTP check. By default, 220 and 250.
* Add possible work with new Doctrine DBAL ~3.0 versions.

v1.3.2
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\Mailer\SymfonyMailerSmtpConnectionCheck` - check connect to SMTP via Symfony Mailer Transport.

v1.3.1
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpLib\RabbitMqSocketConnectionCheck` - check connect to RabbitMQ via socket with `AmqpLib`.
* `\FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpLib\RabbitMqStreamConnectionCheck` - check connect to RabbitMQ via stream with `AmqpLib`.

v1.3.0
------

* Add support Symfony 6.0

v1.2.2
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\Doctrine\SqlModeDbalCheck` - check SQL modes in your database via Doctrine DBAL connection.
* `\FiveLab\Component\Diagnostic\Check\IsJsonCheck` - check the input data for correct JSON.

v1.2.1
------

No changes in logic.

v1.2.0
------

* Remove support PHP 7.3 and early (Support only 7.4).
* Add support PHP 8.0
* Add custom `HttpInterface` layer for work with HTTP requests based on PSR-17.

> Note: this changes does not have backward compatibility. We affect all checks based on HTTP requests
> (rabbitmq management checks, http checks). For fix, you must pass correct `HttpInterface` to checks 
> (if you use specific client and request factory, without discovery based on php-http).


v1.1.4
------

* `\FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementQueueCheck` - extends check for check
   max available messages in the queue. 

v1.1.3
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\Environment\EnvExistenceCheck` - for check existence variable in ENV.

v1.1.2
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\PhpIni\PhpIniParameterCheck` - for check php.ini parameters.


v1.1.1
------

Fix `\FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionParameters` for correct
get DNS if username and password not provided.

v1.1.0
------

### HTTP

Use [php-http](http://docs.php-http.org/en/latest/) for http checks (this changes does not has backward compatibility).

Affected checks:

* `\FiveLab\Component\Diagnostic\Check\Http\HttpCheck`
* `\FiveLab\Component\Diagnostic\Check\Http\PingableHttpCheck`

These changes affected only if you pass `Client` directly in `__constuctor`.

### RabbitMQ

Add `\FiveLab\Component\Diagnostic\Check\RabbitMq\RabbitMqConnectionParameters` for collect all required parameters
for connect to RabbitMQ.

This change affected all RabbitMQ checks.

### Other

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementCheck` - check access to RabbitMQ Management via API.
* `\FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementExchangeCheck` - check existence exchange with specified type.
* `\FiveLab\Component\Diagnostic\Check\RabbitMq\Management\RabbitMqManagementQueueCheck` - check existence queue.

v1.0.2
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\Pdo\PdoConnectionCheck` - check connect to database via PDO.
* `\FiveLab\Component\Diagnostic\Check\Aws\DynamoDb\DynamoDbTableExistCheck` - check table exist in [AWS DynamoDB](https://aws.amazon.com/dynamodb/).

v1.0.1
------

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\Http\HttpCheck` - check connect to resource by HTTP.

v1.0.0
------

Initialize library.

Add next checks:

* `\FiveLab\Component\Diagnostic\Check\PathWritableCheck` - check path (file or directory) is writable.
* `\FiveLab\Component\Diagnostic\Check\PathReadableCheck` - check path (file or directory) is readable.
* `\FiveLab\Component\Diagnostic\Check\DiskUsageCheck` - check maximum usage of disk space (in percents).
* `\FiveLab\Component\Diagnostic\Check\RabbitMq\AmqpExt\RabbitMqConnectionCheck` - check the connection to RabbitMQ server.
* `\FiveLab\Component\Diagnostic\Check\ParameterEqualCheck` - check parameters equals.
* `\FiveLab\Component\Diagnostic\Check\Symfony\SymfonyContainerParameterEqualCheck` - check parameter equal from symfony container. 
* `\FiveLab\Component\Diagnostic\Check\Doctrine\DbalConnectionCheck` - check the connection to DB via Doctrine DBAL.
* `\FiveLab\Component\Diagnostic\Check\Redis\RedisExt\RedisSetGetCheck` - check the connection to Redis.
* `\FiveLab\Component\Diagnostic\Check\Mailer\SwiftMailerConnectionCheck` - check the connection to mailer via SwiftMailer.
* `\FiveLab\Component\Diagnostic\Check\Http\PingableHttpCheck` - check the version via pingable functionality.
* `\FiveLab\Component\Diagnostic\Check\Doctrine\DbalMysqlVersionCheck` - check MySQL version for Doctrine DBAL connection.
* `\FiveLab\Component\Diagnostic\Check\Redis\Predis\PredisSetGetCheck` - check via [predis](https://packagist.org/packages/predis/predis) library.
* `\FiveLab\Component\Diagnostic\Check\Environment\EnvVarRegexCheck` - check environment variable against a regex pattern
* `\FiveLab\Component\Diagnostic\Check\Doctrine\ReadAccessToTablesFromEntityManagerCheck` - check for db tables read access
* `\FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchConnectionCheck` - check connect to Elasticsearch.
* `\FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchTemplateCheck` - check Elasticsearch template.
* `\FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchVersionCheck` - check Elasticsearch version.
* `\FiveLab\Component\Diagnostic\Check\Eloquent\DatabaseConnectionCheck` - check db connection via Eloquent.
* `\FiveLab\Component\Diagnostic\Check\Eloquent\DatabaseMysqlVersionCheck` - check MySQL version via Eloquent.
* `\FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchClusterStateCheck` - check ES cluster health check.
* `\FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchIndexCheck` - check ES index.
