<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use React\Stream\ThroughStream;
use S3\Log\Viewer\Controller\StreamAction;
use S3\Log\Viewer\LogService;

class StreamActionTest extends TestCase
{
    /** @throws Exception */
    public function test_invoke_returns_correct_streaming_response(): void
    {
        $mockStream = new ThroughStream();
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Last-Event-ID')->willReturn('123');

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('createChannelStream')
            ->with($mockStream, '123');

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
    }

    /** @throws Exception */
    public function test_invoke_handles_missing_last_event_id_correctly(): void
    {
        $mockStream = new ThroughStream();
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Last-Event-ID')->willReturn('');

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('createChannelStream')
            ->with($mockStream, '');

        $action = new StreamAction($logService);
        $action->__invoke($request);
    }
}
