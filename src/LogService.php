<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use S3\Log\Viewer\Dto\LogEntry;
use S3\Log\Viewer\Dto\LogEntryView;
use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\EventDispatcher;
use S3\Log\Viewer\Storage\LogStorage;

readonly class LogService
{
    public function __construct(
        private LogStorage $storage,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    public function add(LogEntry $log): void
    {
        $this->storage->add($log);
        // Dispatch event with log data for SSE
        $json = json_encode([
            'datetime' => $log->datetime,
            'channel' => $log->channel,
            'level' => $log->level,
            'message' => $log->message,
        ]);
        $this->eventDispatcher->dispatch(new LogReceived($json ?: '{}'));
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
