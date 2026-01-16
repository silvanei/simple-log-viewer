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
use Throwable;

readonly class ApiLogsAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $contentType = $request->getHeaderLine('Content-Type');
            if ($contentType !== 'application/json') {
                return new Response(415, ['Content-Type' => 'text/html'], 'Unsupported Media Type');
            }

            $json = (string)$request->getBody();
            $logEntry = LogEntryParser::parseJson($json);

            $this->logService->add($logEntry);

            return new Response(201, body: 'Received log');
        } catch (InvalidLogEntryDataException $e) {
            return new Response(400, ['Content-Type' => 'application/json'], json_encode(['errors' => $e->errors]) ?: '');
        } catch (Throwable $e) {
            return new Response(400, ['Content-Type' => 'text/html'], $e->getMessage());
        }
    }
}
