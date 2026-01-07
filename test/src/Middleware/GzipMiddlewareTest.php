<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Middleware;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\Middleware\GzipMiddleware;

class GzipMiddlewareTest extends TestCase
{
    private GzipMiddleware $middleware;
    private ServerRequestInterface&MockObject $request;

    #[Before]
    protected function init(): void
    {
        $this->middleware = new GzipMiddleware(minSize: 1024, compressionLevel: 6);
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    public function testCompressesLargeHtmlResponse(): void
    {
        $body = str_repeat('<p>conteudo grande</p>', 200);
        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip, deflate');
        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body);

        $response = ($this->middleware)($this->request, $next);

        $compressedBody = (string) $response->getBody();
        $this->assertSame('gzip', $response->getHeaderLine('Content-Encoding'));
        $this->assertSame('Accept-Encoding', $response->getHeaderLine('Vary'));
        $this->assertNotSame($body, $compressedBody);
        $this->assertSame($body, gzdecode($compressedBody));
    }

    public function testSkipsCompressionWhenBodyIsTooSmall(): void
    {
        $body = 'small content';
        $this->request
            ->expects($this->never())
            ->method('getHeaderLine');
        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body);

        $response = ($this->middleware)($this->request, $next);

        $this->assertFalse($response->hasHeader('Content-Encoding'));
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testSkipsCompressionWhenClientDoesNotAcceptGzip(): void
    {
        $body = str_repeat('content', 300);
        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('identity');
        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body);

        $response = ($this->middleware)($this->request, $next);

        $this->assertFalse($response->hasHeader('Content-Encoding'));
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testSkipsCompressionWhenAlreadyEncoded(): void
    {
        $body = str_repeat('content', 300);
        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip');
        $next = fn () => new Response(200, [ 'Content-Type'     => 'text/html', 'Content-Encoding' => 'br', ], $body);

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame('br', $response->getHeaderLine('Content-Encoding'));
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testSkipsCompressionForUnsupportedContentType(): void
    {
        $body = str_repeat('binary', 300);
        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip');
        $next = fn () => new Response(200, ['Content-Type' => 'image/png'], $body);

        $response = ($this->middleware)($this->request, $next);

        $this->assertFalse($response->hasHeader('Content-Encoding'));
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testSkipsCompressionWhenBodyIsEmpty(): void
    {
        $this->request
            ->expects($this->never())
            ->method('getHeaderLine');
        $next = fn () => new Response(204, ['Content-Type' => 'text/html'], '');

        $response = ($this->middleware)($this->request, $next);

        $this->assertFalse($response->hasHeader('Content-Encoding'));
        $this->assertSame('', (string) $response->getBody());
    }
}
