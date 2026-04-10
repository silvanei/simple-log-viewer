<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Http;

use Nyholm\Psr7\Response as PsrResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Response wrapper providing static factory methods compatible with ReactPHP's Response class.
 *
 * This class maintains backward compatibility with code that uses:
 * - Response::html($body, $status, $headers)
 * - Response::json($data, $status, $headers)
 */
final class Response
{
    /**
     * Create an HTML response.
     *
     * @param array<string, string> $headers
     */
    public static function html(string $body, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';

        return new PsrResponse($status, $headers, $body);
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data Data to be encoded as JSON
     * @param array<string, string> $headers
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/json';

        return new PsrResponse($status, $headers, json_encode($data) ?: '');
    }

    /**
     * Create a generic response with custom body and headers.
     *
     * @param array<string, string> $headers
     */
    public static function create(string $body, int $status = 200, array $headers = []): ResponseInterface
    {
        return new PsrResponse($status, $headers, $body);
    }
}
