<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

readonly class StaticFileMiddleware
{
    private string $publicPath;
    /** @var array<string, string>  */
    private array $mimeTypes;

    public function __construct(string $publicPath)
    {
        $this->publicPath = realpath($publicPath) ?: '/app/public';
        $this->mimeTypes = [
            'html' => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'ico'  => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];
    }

    /** @param callable(ServerRequestInterface):ResponseInterface  $next */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $uriPath = rawurldecode($request->getUri()->getPath());
        $filePath = realpath($this->publicPath . $uriPath);

        if (
            $filePath !== false
            && str_starts_with($filePath, $this->publicPath)
            && is_file($filePath)
        ) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $mime = $this->mimeTypes[$ext] ?? 'application/octet-stream';

            return new Response(
                200,
                [
                    'Content-Type'   => $mime,
                    'Cache-Control'  => 'public, max-age=86400',
                ],
                file_get_contents($filePath) ?: '',
            );
        }

        return $next($request);
    }
}
