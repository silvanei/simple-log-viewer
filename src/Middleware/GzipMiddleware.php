<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Io\BufferedBody;

readonly class GzipMiddleware
{
    public function __construct(private int $minSize = 1024, private int $compressionLevel = 6)
    {
    }

    /** @param callable(ServerRequestInterface):ResponseInterface  $next */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $response = $next($request);

        $body = (string) $response->getBody();
        if ($body === '') {
            return $response;
        }

        if (strlen($body) < $this->minSize) {
            return $response;
        }

        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
        if (! str_contains($acceptEncoding, 'gzip')) {
            return $response;
        }

        if ($response->hasHeader('Content-Encoding')) {
            return $response;
        }

        $contentType = $response->getHeaderLine('Content-Type');
        if (! preg_match('#text/|application/json|application/javascript#', $contentType)) {
            return $response;
        }

        $compressed = gzencode($body, $this->compressionLevel) ?: '';
        return $response
            ->withHeader('Content-Encoding', 'gzip')
            ->withHeader('Content-Length', (string)strlen($compressed))
            ->withHeader('Vary', 'Accept-Encoding')
            ->withBody(new BufferedBody($compressed))
        ;
    }
}
