<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Stream\ThroughStream;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\LogService;

readonly class StreamAction implements ActionHandler
{
    public function __construct(private LogService $logService)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $stream = new ThroughStream();
        $id = $request->getHeaderLine('Last-Event-ID');
        $this->logService->channel($stream, $id);

        return new Response(
            200,
            ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache'],
            $stream,
        );
    }
}
