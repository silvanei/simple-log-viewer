<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Sse;

/**
 * FrankenPHP compatible SSE connection using output buffering
 */
final class FrankenPhpSseConnection implements SseConnectionInterface
{
    private string $id;

    private bool $active = true;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? uniqid('sse_', true);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function send(string $data): void
    {
        if (! $this->active) {
            return;
        }

        echo $data;

        if (function_exists('frankenphp_finish_request')) {
            frankenphp_finish_request();
        } else {
            flush();
        }
    }

    public function isActive(): bool
    {
        if (! $this->active) {
            return false;
        }

        // Check if connection is still open
        if (connection_aborted()) {
            $this->active = false;

            return false;
        }

        return true;
    }

    public function close(): void
    {
        $this->active = false;
    }
}
