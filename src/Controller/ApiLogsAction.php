<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
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
            /** @var array{'datetime': string, 'channel': string, 'level': string, 'message': string, 'context': string} $data */
            $data = json_decode((string)$request->getBody(), associative: true, flags: JSON_THROW_ON_ERROR);
            $this->logService->add($data);

            return new Response(201, body: 'Received log');
        } catch (Throwable $e) {
            return new Response(400, ['Content-Type' => 'text/html'], $e->getMessage());
        }
    }
}
