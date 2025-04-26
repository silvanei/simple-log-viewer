<?php

declare(strict_types=1);

use S3\Log\Viewer\Application;
use S3\Log\Viewer\Controller\ApiLogsAction;
use S3\Log\Viewer\Controller\HomeAction;
use S3\Log\Viewer\Controller\SearchAction;
use S3\Log\Viewer\Controller\StreamAction;
use S3\Log\Viewer\LogService;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

(static function () {
    $databaseDsn = getenv('DATABASE_DSN') ?: ':memory:';
    if ($databaseDsn !== ':memory:' and ! file_exists($databaseDsn)) {
        touch($databaseDsn);
    }
    $storage = new PDO("sqlite:$databaseDsn");
    $logService = new LogService($storage);

    $application = new Application();
    $application->get('/', new HomeAction());
    $application->get('/logs-stream', new StreamAction($logService));
    $application->get('/search', new SearchAction($logService));
    $application->post('/api/logs', new ApiLogsAction($logService));

    $application->listen('0.0.0.0:8080');
})();
