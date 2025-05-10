<?php

declare(strict_types=1);

namespace Test\Support\S3\Log\Viewer\EventDispatcher;

use S3\Log\Viewer\EventDispatcher\EventHandler;
use stdClass;

class StdClassEventHandler
{
    #[EventHandler]
    public function handleStdClassEvent(stdClass $event): void
    {
    }
}
