<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Dto;

final readonly class LogEntry
{
    /**
     * @param array<string|int, mixed> $context
     * @param array<string|int, mixed> $extra
     */
    public function __construct(
        public string $datetime,
        public string $channel,
        public string $level,
        public string $message,
        public array $context,
        public array $extra = [],
    ) {
    }
}
