<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\EventDispatcher\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\Handler\StreamChannelHandler;
use S3\Log\Viewer\Sse\MercurePublisher;

class StreamChannelHandlerTest extends TestCase
{
    private MercurePublisher&MockObject $publisher;
    private StreamChannelHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publisher = $this->createMock(MercurePublisher::class);
        $this->handler = new StreamChannelHandler($this->publisher);
    }

    public function testHandleLogReceived_ShouldPublishToMercure(): void
    {
        $event = new LogReceived('New log received');

        $this->publisher
            ->expects($this->once())
            ->method('publish')
            ->with('logs', 'New log received');

        $this->handler->handleLogReceived($event);
    }

    public function testHandleLogCleared_ShouldPublishToMercure(): void
    {
        $event = new LogCleared('Logs cleared');

        $this->publisher
            ->expects($this->once())
            ->method('publish')
            ->with('logs', 'Logs cleared');

        $this->handler->handleLogCleared($event);
    }
}
