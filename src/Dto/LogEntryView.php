<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Dto;

final readonly class LogEntryView
{
    public function __construct(
        public string $datetime,
        public string $channel,
        public string $level,
        public string $message,
        /** @var array<string|int, mixed> $context */
        public array $context,
        /** @var array<string|int, mixed> $extra */
        public array $extra = [],
    ) {
    }
}
