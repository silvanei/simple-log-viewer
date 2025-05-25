<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use JsonException;
use React\Stream\ThroughStream;
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

    /** @param array{datetime: string, channel: string, level: string, message: string, context: array<string|int, mixed>, extra?: array<string|int, mixed>} $log */
    public function add(array $log): void
    {
        $this->storage->add($log);
        $this->eventDispatcher->dispatch(new LogReceived());
    }

    /** @return array{datetime: string, channel: string, level: string, message: string, context: array<string|int, mixed>, extra?: array<string|int, mixed>}[] */
    public function search(string $filter): array
    {
        return array_map(
            function (array $row): array {
                $row['context'] = $this->jsonDecode($row['context']);
                $row['extra'] = $this->jsonDecode($row['extra']);
                return $row;
            },
            $this->storage->search($filter)
        );
    }

    public function clear(): void
    {
        $this->storage->clear();
        $this->eventDispatcher->dispatch(new LogCleared());
    }

    /** @return array<int|string, mixed> */
    private function jsonDecode(string $json): array
    {
        try {
            /** @var array<int|string, mixed> $data */
            $data = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        return $data;
    }
}
