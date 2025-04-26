<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\LogService;
use Throwable;

readonly class SearchAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var array{search?: string} $queryParams */
            $queryParams = $request->getQueryParams();
            $filter = $queryParams['search'] ?? '';
            $html = $this->logService->search($filter);

            return new Response(200, ['Content-Type' => 'text/html'], $html);
        } catch (Throwable $e) {
            return new Response(400, ['Content-Type' => 'text/html'], $e->getMessage());
        }
    }
}
