<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\Dto\InvalidLogEntryDataException;
use S3\Log\Viewer\Dto\LogEntryParser;
use S3\Log\Viewer\LogService;

readonly class ApiLogsAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if ($contentType !== 'application/json') {
            return Response::json(['error' => 'Unsupported Media Type. Expected application/json'])->withStatus(415);
        }

        $json = (string)$request->getBody();
        try {
            $logEntry = LogEntryParser::parseJson($json);

            $this->logService->add($logEntry);

            return Response::json(['message' => 'Log entry received'])->withStatus(201);
        } catch (InvalidLogEntryDataException $e) {
            return Response::json(['errors' => $e->errors, 'error' => 'Validation failed'])->withStatus(400);
        }
    }
}
