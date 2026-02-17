<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher\Handler;

use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\Event\StreamCreated;
use S3\Log\Viewer\EventDispatcher\EventHandler;
use S3\Log\Viewer\Sse\SseChannel;

final readonly class StreamChannelHandler
{
    public function __construct(
        private SseChannel $channel = new SseChannel(),
    ) {
    }

    #[EventHandler]
    public function handleStreamCreated(StreamCreated $event): void
    {
        $this->channel->connect($event->connection);
        $this->channel->replayBuffer($event->connection);

        // Set up cleanup when connection closes
        $event->connection->send(""); // Send initial keep-alive
    }

    #[EventHandler]
    public function handleLogReceived(LogReceived $event): void
    {
        $this->channel->writeMessage($event->message);
    }

    #[EventHandler]
    public function handleLogCleared(LogCleared $event): void
    {
        $this->channel->writeMessage($event->message);
    }
}
