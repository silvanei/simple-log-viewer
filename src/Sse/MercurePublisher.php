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
        // Use FrankenPHP's built-in mercure_publish() function
        if (function_exists('mercure_publish')) {
            try {
                mercure_publish($topic, $message);
                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        // Fallback: try HTTP POST
        return $this->publishViaHttp($topic, $message);
    }

    /**
     * Fallback publish via HTTP.
     */
    private function publishViaHttp(string $topic, string $message): bool
    {
        $url = 'http://localhost:5000/.well-known/mercure';

        $postData = [
            'topic' => $topic,
            'data' => $message,
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                'content' => http_build_query($postData),
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return $result !== false;
    }
}
