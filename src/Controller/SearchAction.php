<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\Dto\LogEntryView;
use S3\Log\Viewer\LogService;
use S3\Log\Viewer\View\Search\SearchViewModel;

readonly class SearchAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{search?: string, fields?: string[]} $queryParams */
        $queryParams = $request->getQueryParams();
        $fields = $queryParams['fields'] ?? [];
        $filter = $queryParams['search'] ?? '';

        /** @var LogEntryView[] $rows */
        $rows = $this->logService->search($filter);
        $totalResults = count($rows);
        $view = new SearchViewModel('search/log-entry', ['entries' => $rows, 'fields' => $fields, 'totalResults' => $totalResults]);

        return Response::html($view->render());
    }
}
