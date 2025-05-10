<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\support\EventDispatcher;

use S3\Log\Viewer\EventDispatcher\EventHandler;
use stdClass;

class StdClassEventHandler
{
    #[EventHandler]
    public function handleStdClassEvent(stdClass $event): void
    {
    }
}
