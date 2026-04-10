<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use S3\Log\Viewer\Controller\HomeAction;

class HomeActionTest extends TestCase
{
    public function testInvoke_ShouldReturnsResponseWith200StatusCode(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvokeSetsCorrectContentTypeHeader(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
    }

    public function testInvokeReturnsHtmlWithMercureScript(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $body = (string) $response->getBody();

        // Verify HTMX SSE attributes are present
        $this->assertStringContainsString('hx-ext="sse"', $body);
        $this->assertStringContainsString('sse-connect="/.well-known/mercure?topic=logs"', $body);
        $this->assertStringContainsString('sse-swap="message"', $body);
    }
}
