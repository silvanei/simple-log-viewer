<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventBus;

interface EventBus
{
    public function dispatch(object $event): void;
}
