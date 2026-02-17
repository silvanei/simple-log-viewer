<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use S3\Log\Viewer\Controller\StreamAction;
use S3\Log\Viewer\LogService;
use S3\Log\Viewer\Sse\SseConnectionInterface;

class StreamActionTest extends TestCase
{
    /** @throws Exception */
    public function test_invoke_returns_correct_streaming_response(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Last-Event-ID')
            ->willReturn('123');

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('createChannelStream')
            ->with($this->isInstanceOf(SseConnectionInterface::class), '123');

        $action = new StreamAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['text/event-stream'],
            $response->getHeader('Content-Type')
        );
        $this->assertSame(
            ['no-cache'],
            $response->getHeader('Cache-Control')
        );
        $this->assertSame(
            ['no'],
            $response->getHeader('X-Accel-Buffering')
        );
    }

    /** @throws Exception */
    public function test_invoke_handles_missing_last_event_id_correctly(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Last-Event-ID')
            ->willReturn('');

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('createChannelStream')
            ->with($this->isInstanceOf(SseConnectionInterface::class), '');

        $action = new StreamAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
