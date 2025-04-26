<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Handler;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

readonly class RouterHandler
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array{0: int, 1: callable} $routeInfo */
        $routeInfo = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        return match ($routeInfo[0]) {
            Dispatcher::FOUND => $this->dispatch($request, $routeInfo),
            Dispatcher::METHOD_NOT_ALLOWED => new Response(405, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Method not allowed'])),
            default => new Response(404, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Not found'])),
        };
    }

    /** @param array{0: int, 1: callable} $routeInfo */
    private function dispatch(ServerRequestInterface $request, array $routeInfo): ResponseInterface
    {
        try {
            return call_user_func_array($routeInfo[1], [$request]);
        } catch (\Throwable $e) {
            return new Response(500, ['Content-Type' => 'application/json'], (string)json_encode(['error' => 'Internal Server Error']));
        }
    }
}
