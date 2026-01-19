<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\LogService;

readonly class ClearLogsAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->logService->clear();
        return Response::html('');
    }
}
