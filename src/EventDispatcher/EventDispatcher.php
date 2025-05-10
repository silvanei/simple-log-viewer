<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher;

interface EventDispatcher
{
    public function dispatch(object $event): void;
}
