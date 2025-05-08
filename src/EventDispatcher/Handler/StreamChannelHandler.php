<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher\Handler;

use Clue\React\Sse\BufferedChannel;
use React\EventLoop\Loop;
use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\Event\StreamCreated;
use S3\Log\Viewer\EventDispatcher\EventHandler;

final readonly class StreamChannelHandler
{
    public function __construct(
        private BufferedChannel $channel = new BufferedChannel(),
    ) {
    }

    #[EventHandler]
    public function handlerStreamCreated(StreamCreated $event): void
    {
        Loop::get()->futureTick(fn() => $this->channel->connect($event->stream, $event->id));
        $event->stream->on('close', fn() => $this->channel->disconnect($event->stream));
    }

    #[EventHandler]
    public function handlerLogReceived(LogReceived $event): void
    {
        $this->channel->writeMessage($event->message);
    }

    #[EventHandler]
    public function handlerLogCleared(LogCleared $event): void
    {
        $this->channel->writeMessage($event->message);
    }
}
