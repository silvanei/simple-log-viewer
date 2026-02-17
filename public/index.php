<?php

declare(strict_types=1);

ignore_user_abort(true);

require __DIR__ . '/../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

// Initialize app once
$application = (static function (): \S3\Log\Viewer\Application {
    $databaseDsn = getenv('DATABASE_DSN') ?: ':memory:';
    if ($databaseDsn !== ':memory:' && ! file_exists($databaseDsn)) {
        touch($databaseDsn);
    }

    $storage = new PDO("sqlite:$databaseDsn");
    $channel = new \Clue\React\Sse\BufferedChannel();
    $eventDispatcher = new \S3\Log\Viewer\EventDispatcher\GenericEventDispatcher(
        new \S3\Log\Viewer\EventDispatcher\Handler\StreamChannelHandler($channel)
    );
    $logService = new \S3\Log\Viewer\LogService(
        new \S3\Log\Viewer\Storage\LogStorageSQLite($storage),
        $eventDispatcher
    );

    $app = new \S3\Log\Viewer\Application();
    $app->get('/', new \S3\Log\Viewer\Controller\HomeAction());
    $app->get('/logs-stream', new \S3\Log\Viewer\Controller\StreamAction($logService));
    $app->get('/search', new \S3\Log\Viewer\Controller\SearchAction($logService));
    $app->post('/api/logs', new \S3\Log\Viewer\Controller\ApiLogsAction($logService));
    $app->post('/api/logs/clear', new \S3\Log\Viewer\Controller\ClearLogsAction($logService));

    return $app;
})();

// Request handler
$handler = static function () use ($application): void {
    // Create PSR-7 request from superglobals
    $creator = new ServerRequestCreator(
        new Psr17Factory(),
        new Psr17Factory(),
        new Psr17Factory(),
        new Psr17Factory()
    );

    $psrRequest = $creator->fromGlobals();

    // Handle request and get response
    $psrResponse = $application->handle($psrRequest);

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
};

// Process requests
$maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
for ($nbRequests = 0; ! $maxRequests || $nbRequests < $maxRequests; ++$nbRequests) {
    $keepRunning = \frankenphp_handle_request($handler);
    gc_collect_cycles();
    if (! $keepRunning) {
        break;
    }
}
