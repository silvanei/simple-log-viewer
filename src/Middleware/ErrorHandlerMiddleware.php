<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use Throwable;

final readonly class ErrorHandlerMiddleware
{
    /** @param callable(ServerRequestInterface):ResponseInterface  $next */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            if ($this->isApiRequest($request)) {
                return Response::json(['error' => 'Internal server error'])->withStatus(500);
            }
            return Response::html('<h1>An error occurred</h1>')->withStatus(500);
        }
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), '/api/');
    }
}
