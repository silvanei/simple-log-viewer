<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\LogService;
use S3\Log\Viewer\View\Search\SearchViewModel;
use Throwable;

readonly class SearchAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var array{search?: string, fields?: string[]} $queryParams */
            $queryParams = $request->getQueryParams();
            $fields = $queryParams['fields'] ?? [];
            $filter = $queryParams['search'] ?? '';

            $rows = $this->logService->search($filter);
            $view = new SearchViewModel('search/log-entry', ['entries' => $rows, 'fields' => $fields]);

            return Response::html($view->render());
        } catch (Throwable $e) {
            return new Response(400, ['Content-Type' => 'text/html'], $e->getMessage());
        }
    }
}
