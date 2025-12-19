<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Handler;

use FastRoute\Dispatcher;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\Handler\RouterHandler;

class RouterHandlerTest extends TestCase
{
    private Dispatcher&MockObject $dispatcher;
    private ServerRequestInterface&MockObject $request;

    /** @throws Exception */
    #[Before]
    protected function init(): void
    {
        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->request    = $this->createMock(ServerRequestInterface::class);

        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $uri = $this->createMock(UriInterface::class);
        $uri
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('/foo');

        $this->request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);
    }

    public function testReturnsResponseFromFoundRoute(): void
    {
        $handler = $this->createMock(ActionHandler::class);
        $handler
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(new Response(200, ['Content-Type' => 'text/plain'], 'OK'));
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('GET', '/foo')
            ->willReturn([Dispatcher::FOUND, $handler]);

        $router = new RouterHandler($this->dispatcher);
        $response = $router($this->request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', (string)$response->getBody());
    }

    public function testReturns405WhenMethodNotAllowed(): void
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('GET', '/foo')
            ->willReturn([Dispatcher::METHOD_NOT_ALLOWED, []]);

        $router = new RouterHandler($this->dispatcher);
        $response = $router($this->request);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertStringContainsString('"error":"Method not allowed"', (string)$response->getBody());
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testReturns404WhenNotFound(): void
    {
        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('GET', '/foo')
            ->willReturn([Dispatcher::NOT_FOUND, []]);

        $router = new RouterHandler($this->dispatcher);
        $response = $router($this->request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('"error":"Not found"', (string)$response->getBody());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testHandlerExceptionResultsIn500(): void
    {
        $handler = fn() => throw new \RuntimeException('oops');

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with('GET', '/foo')
            ->willReturn([Dispatcher::FOUND, $handler]);

        $router = new RouterHandler($this->dispatcher);
        $response = $router($this->request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertStringContainsString('"error":"Internal Server Error"', (string)$response->getBody());
    }
}
