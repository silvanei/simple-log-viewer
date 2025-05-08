<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Storage;

interface LogStorage
{
    /**
     * @param array{datetime: string, channel: string, level: string, message: string, context: array<string|int, mixed>} $log
     */
    public function add(array $log): void;

    /** @return array{datetime: string, channel: string, level: string, message: string, context: string}[] */
    public function search(string $filter): array;

    public function clear(): void;
}
