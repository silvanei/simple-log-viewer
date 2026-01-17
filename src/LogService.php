<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use React\Stream\ThroughStream;
use S3\Log\Viewer\Dto\LogEntry;
use S3\Log\Viewer\Dto\LogEntryView;
use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\Event\StreamCreated;
use S3\Log\Viewer\EventDispatcher\EventDispatcher;
use S3\Log\Viewer\Storage\LogStorage;

readonly class LogService
{
    public function __construct(
        private LogStorage $storage,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function createChannelStream(ThroughStream $stream, string $id): void
    {
        $this->eventDispatcher->dispatch(new StreamCreated($stream, $id));
    }

    public function add(LogEntry $log): void
    {
        $this->storage->add($log);
        $this->eventDispatcher->dispatch(new LogReceived());
    }

    /** @return LogEntryView[] */
    public function search(string $filter): array
    {
        return $this->storage->search($filter);
    }

    public function clear(): void
    {
        $this->storage->clear();
        $this->eventDispatcher->dispatch(new LogCleared());
    }
}
