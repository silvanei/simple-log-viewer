<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\LogService;
use Throwable;

readonly class ClearLogsAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logService->clear();
            return new Response(200, ['Content-Type' => 'text/html'], '');
        } catch (Throwable $e) {
            return new Response(500, ['Content-Type' => 'text/html'], $e->getMessage());
        }
    }
}
