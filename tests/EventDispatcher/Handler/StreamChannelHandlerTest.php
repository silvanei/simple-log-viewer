<?php

declare(strict_types=1);

namespace EventDispatcher\Handler;

use Clue\React\Sse\BufferedChannel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use React\Stream\ThroughStream;
use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\Event\StreamCreated;
use S3\Log\Viewer\EventDispatcher\Handler\StreamChannelHandler;

class StreamChannelHandlerTest extends TestCase
{
    private BufferedChannel&MockObject $bufferedChannel;
    private StreamChannelHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bufferedChannel = $this->createMock(BufferedChannel::class);
        $this->handler = new StreamChannelHandler($this->bufferedChannel);
    }


    public function testHandlerStreamCreatedConnect_ShouldCallChannelConnect_WhenLoopRun(): void
    {
        $stream = new ThroughStream();
        $id = 'abc123';
        $this->bufferedChannel
            ->expects($this->once())
            ->method('connect')
            ->with($stream, $id);

        $this->handler->handlerStreamCreated(new StreamCreated($stream, $id));

        Loop::run();
    }

    public function testHandlerStreamCreatedConnect_ShouldCallChannelDisconnect_WhenStreamEmitCloseEvent(): void
    {
        $stream = new ThroughStream();
        $id = 'abc123';
        $this->bufferedChannel
            ->expects($this->once())
            ->method('disconnect')
            ->with($stream);

        $this->handler->handlerStreamCreated(new StreamCreated($stream, $id));

        $stream->emit('close');
    }

    public function testHandlerLogReceived_ShouldCallChannelWriteMessage(): void
    {
        $expectedEvent = new LogReceived();
        $this->bufferedChannel
            ->expects($this->once())
            ->method('writeMessage')
            ->with($expectedEvent->message);

        $this->handler->handlerLogReceived($expectedEvent);
    }

    public function testHandlerLogCleared_ShouldCallChannelWriteMessage(): void
    {
        $expectedEvent = new LogCleared();
        $this->bufferedChannel
            ->expects($this->once())
            ->method('writeMessage')
            ->with($expectedEvent->message);

        $this->handler->handlerLogCleared($expectedEvent);
    }
}
