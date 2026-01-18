<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Middleware;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\Middleware\GzipMiddleware;

#[AllowMockObjectsWithoutExpectations]
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

    public function testCompressesWithDefaultCompressionLevel(): void
    {
        $middleware = new GzipMiddleware();
        $body = str_repeat('content', 300);

        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip, deflate');

        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body);

        $response = ($middleware)($this->request, $next);

        $this->assertSame('gzip', $response->getHeaderLine('Content-Encoding'));
        $this->assertSame('Accept-Encoding', $response->getHeaderLine('Vary'));
        $this->assertNotSame($body, (string) $response->getBody());
        $this->assertSame($body, gzdecode((string) $response->getBody()));
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

    public function testSkipsCompressionWhenBodySizeIsExactlyMinSize(): void
    {
        $middleware = new GzipMiddleware();
        $body = str_repeat('x', 1024);

        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('identity');

        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body);

        $response = ($middleware)($this->request, $next);

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
        $originalBody = '';

        $this->request
            ->expects($this->never())
            ->method('getHeaderLine');
        $next = fn () => new Response(204, ['Content-Type' => 'text/html'], $originalBody);

        $response = ($this->middleware)($this->request, $next);

        $this->assertFalse($response->hasHeader('Content-Encoding'));
        $this->assertSame($originalBody, (string) $response->getBody());
    }

    public function testUsesDefaultMinSizeOf1024(): void
    {
        $middleware = new GzipMiddleware();
        $body1023 = str_repeat('x', 1023);
        $body1024 = str_repeat('x', 1024);

        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip');

        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body1023);
        $response = ($middleware)($this->request, $next);
        $this->assertFalse($response->hasHeader('Content-Encoding'));

        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip');

        $next2 = fn () => new Response(200, ['Content-Type' => 'text/html'], $body1024);
        $response2 = ($middleware)($this->request, $next2);
        $this->assertSame('gzip', $response2->getHeaderLine('Content-Encoding'));
    }

    public function testUsesDefaultCompressionLevelOf6(): void
    {
        $middleware = new GzipMiddleware();
        $body = str_repeat('test content for compression verification ', 100);

        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip');

        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body);
        $response = ($middleware)($requestMock, $next);

        $this->assertSame('gzip', $response->getHeaderLine('Content-Encoding'));
        $compressed = (string) $response->getBody();
        $this->assertNotSame($body, $compressed);
        $this->assertSame($body, gzdecode($compressed));
    }

    public function testCompressesBodyExactlyAtMinSizeThreshold(): void
    {
        $middleware = new GzipMiddleware(minSize: 1024);
        $body = str_repeat('x', 1024);

        $this->request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Accept-Encoding')
            ->willReturn('gzip');

        $next = fn () => new Response(200, ['Content-Type' => 'text/html'], $body);

        $response = ($middleware)($this->request, $next);

        $this->assertSame('gzip', $response->getHeaderLine('Content-Encoding'));
        $compressed = (string) $response->getBody();
        $this->assertNotSame($body, $compressed);
        $this->assertSame($body, gzdecode($compressed));
    }
}
