<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher\Handler;

use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\EventHandler;
use S3\Log\Viewer\Sse\MercurePublisher;

final readonly class StreamChannelHandler
{
    public function __construct(
        private MercurePublisher $publisher = new MercurePublisher(),
    ) {
    }

    #[EventHandler]
    public function handleLogReceived(LogReceived $event): void
    {
        // Publish to Mercure hub for SSE
        $this->publisher->publish('logs', $event->message);
    }

    #[EventHandler]
    public function handleLogCleared(LogCleared $event): void
    {
        // Publish clear event to Mercure
        $this->publisher->publish('logs', $event->message);
    }
}
