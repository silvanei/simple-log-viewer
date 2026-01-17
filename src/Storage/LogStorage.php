<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Storage;

use S3\Log\Viewer\Dto\LogEntry;
use S3\Log\Viewer\Dto\LogEntryView;

interface LogStorage
{
    public function add(LogEntry $log): void;

    /** @return LogEntryView[] */
    public function search(string $filter): array;

    public function clear(): void;
}
