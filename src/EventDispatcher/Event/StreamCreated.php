<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher\Event;

use React\Stream\ThroughStream;

final readonly class StreamCreated
{
    public function __construct(public ThroughStream $stream, public string $id)
    {
    }
}
