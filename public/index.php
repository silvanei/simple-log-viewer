<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

$databaseDsn = getenv('DATABASE_DSN') ?: '/data/storage/logs.db';
$storageDir = dirname($databaseDsn);
if (! is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
if ($databaseDsn !== ':memory:' && ! file_exists($databaseDsn)) {
    touch($databaseDsn);
}

$storage = new PDO("sqlite:$databaseDsn");
$eventDispatcher = new \S3\Log\Viewer\EventDispatcher\GenericEventDispatcher(
    new \S3\Log\Viewer\EventDispatcher\Handler\StreamChannelHandler()
);
$logService = new \S3\Log\Viewer\LogService(
    new \S3\Log\Viewer\Storage\LogStorageSQLite($storage),
    $eventDispatcher
);

$app = new \S3\Log\Viewer\Application();
$app->get('/', new \S3\Log\Viewer\Controller\HomeAction());
$app->get('/search', new \S3\Log\Viewer\Controller\SearchAction($logService));
$app->post('/api/logs', new \S3\Log\Viewer\Controller\ApiLogsAction($logService));
$app->post('/api/logs/clear', new \S3\Log\Viewer\Controller\ClearLogsAction($logService));

$creator = new ServerRequestCreator(
    new Psr17Factory(),
    new Psr17Factory(),
    new Psr17Factory(),
    new Psr17Factory()
);

$psrRequest = $creator->fromGlobals();

// Handle request and get response
$psrResponse = $app->handle($psrRequest);

// Send response headers
foreach ($psrResponse->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

// Send status code
http_response_code($psrResponse->getStatusCode());

// Send body
echo $psrResponse->getBody();
