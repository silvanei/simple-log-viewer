<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Sse;

/**
 * Interface for SSE stream connections
 */
interface SseConnectionInterface
{
    public function getId(): string;

    public function send(string $data): void;

    public function isActive(): bool;

    public function close(): void;
}
