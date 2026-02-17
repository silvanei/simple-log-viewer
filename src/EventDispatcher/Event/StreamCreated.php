<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher\Event;

use S3\Log\Viewer\Sse\SseConnectionInterface;

final readonly class StreamCreated
{
    public function __construct(public SseConnectionInterface $connection, public string $id)
    {
    }
}
