<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\EventDispatcher\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\Event\StreamCreated;
use S3\Log\Viewer\EventDispatcher\Handler\StreamChannelHandler;
use S3\Log\Viewer\Sse\SseChannel;
use S3\Log\Viewer\Sse\SseConnectionInterface;

class StreamChannelHandlerTest extends TestCase
{
    private SseChannel&MockObject $sseChannel;
    private StreamChannelHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sseChannel = $this->createMock(SseChannel::class);
        $this->handler = new StreamChannelHandler($this->sseChannel);
    }

    public function testHandleStreamCreated_ShouldConnectAndReplayBuffer(): void
    {
        $connection = $this->createMock(SseConnectionInterface::class);
        $id = 'abc123';

        $this->sseChannel
            ->expects($this->once())
            ->method('connect')
            ->with($connection);

        $this->sseChannel
            ->expects($this->once())
            ->method('replayBuffer')
            ->with($connection);

        $connection
            ->expects($this->once())
            ->method('send')
            ->with("");

        $this->handler->handleStreamCreated(new StreamCreated($connection, $id));
    }

    public function testHandleLogReceived_ShouldCallChannelWriteMessage(): void
    {
        $expectedEvent = new LogReceived();
        $this->sseChannel
            ->expects($this->once())
            ->method('writeMessage')
            ->with($expectedEvent->message);

        $this->handler->handleLogReceived($expectedEvent);
    }

    public function testHandleLogCleared_ShouldCallChannelWriteMessage(): void
    {
        $expectedEvent = new LogCleared();
        $this->sseChannel
            ->expects($this->once())
            ->method('writeMessage')
            ->with($expectedEvent->message);

        $this->handler->handleLogCleared($expectedEvent);
    }
}
