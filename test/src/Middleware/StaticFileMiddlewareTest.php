<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Middleware;

use Generator;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\Middleware\StaticFileMiddleware;

class StaticFileMiddlewareTest extends TestCase
{
    private string $publicDir;
    private StaticFileMiddleware $middleware;
    private ServerRequestInterface&MockObject $request;
    private UriInterface&MockObject $uri;
    private ActionHandler&MockObject $next;

    #[Before]
    protected function init(): void
    {
        $this->publicDir = sys_get_temp_dir() . '/static_middleware_test';
        @mkdir($this->publicDir);
        $this->middleware = new StaticFileMiddleware($this->publicDir);

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->uri = $this->createMock(UriInterface::class);
        $this->request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($this->uri);
        $this->next = $this->createMock(ActionHandler::class);
    }

    public static function fileProvider(): Generator
    {
        yield 'html' => ['extension' => 'html', 'expectedMineType' => 'text/html'];
        yield 'css' => ['extension' => 'css', 'expectedMineType' => 'text/css'];
        yield 'js' => ['extension' => 'js', 'expectedMineType' => 'application/javascript'];
        yield 'png' => ['extension' => 'png', 'expectedMineType' => 'image/png'];
        yield 'jpg' => ['extension' => 'jpg', 'expectedMineType' => 'image/jpeg'];
        yield 'svg' => ['extension' => 'svg', 'expectedMineType' => 'image/svg+xml'];
        yield 'woff' => ['extension' => 'woff', 'expectedMineType' => 'font/woff'];
        yield 'unknown' => ['extension' => 'unknown', 'expectedMineType' => 'application/octet-stream'];
    }

    #[DataProvider('fileProvider')]
    public function testServesExistingStaticFile(string $extension, string $expectedMineType): void
    {
        $filename = "test_file.$extension";
        $content  = "file-content-$extension";
        file_put_contents("$this->publicDir/$filename", $content);

        $this->uri->expects($this->once())->method('getPath')->willReturn("/$filename");
        $this->next->expects($this->never())->method('__invoke');

        $response = ($this->middleware)($this->request, $this->next);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedMineType, $response->getHeaderLine('Content-Type'));
        $this->assertSame('public, max-age=86400', $response->getHeaderLine('Cache-Control'));
        $this->assertSame($content, (string)$response->getBody());
    }

    public function testSkipsNonexistentFile(): void
    {
        $this->uri->expects($this->once())->method('getPath')->willReturn('/no-such.file');
        $this->next
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(new Response(418, [], 'I\'m a teapot'));

        $response = ($this->middleware)($this->request, $this->next);

        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame('I\'m a teapot', (string)$response->getBody());
    }

    public function testPreventsDirectoryTraversal(): void
    {
        $outsideFile = sys_get_temp_dir() . '/secret.txt';
        touch($outsideFile);
        $this->uri->expects($this->once())->method('getPath')->willReturn('/../secret.txt');
        $this->next
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(new Response(204));

        $response = ($this->middleware)($this->request, $this->next);

        $this->assertSame(204, $response->getStatusCode());

        unlink($outsideFile);
    }

    #[After]
    protected function clear(): void
    {
        array_map('unlink', glob("$this->publicDir/*") ?: []);
        @rmdir($this->publicDir);
    }
}
