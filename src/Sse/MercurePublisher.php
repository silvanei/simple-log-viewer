<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Sse;

/**
 * Mercure publisher using FrankenPHP's built-in mercure_publish() function.
 */
class MercurePublisher
{
    /**
     * Publish an update to the Mercure hub.
     */
    public function publish(string $topic, string $message): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        try {
            mercure_publish($topic, $message);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Whether FrankenPHP's native Mercure hub function is available.
     *
     * Publishing is only possible inside a FrankenPHP worker with a
     * `mercure` directive configured in the Caddyfile. There is no HTTP
     * fallback: the Mercure 1.0 protocol requires a signed JWT and the
     * legacy `topic` form field is no longer accepted.
     */
    public function isAvailable(): bool
    {
        return function_exists('mercure_publish');
    }
}
