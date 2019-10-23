CHANGELOG
=========

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
* `\FiveLab\Component\Diagnostic\Check\Elasticsearch\ElasticsearchIndicesCheck` - check ES indices settings.