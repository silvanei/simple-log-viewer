<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Storage;

use S3\Log\Viewer\Dto\LogEntry;

interface LogStorage
{
    public function add(LogEntry $log): void;

    /** @return array{datetime: string, channel: string, level: string, message: string, context: string, extra: string}[] */
    public function search(string $filter): array;

    public function clear(): void;
}
