<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventBus\Event;

final readonly class LogCleared
{
    public function __construct(public string $message = 'Logs cleared')
    {
    }
}
