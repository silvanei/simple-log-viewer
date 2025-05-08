<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class EventHandler
{
}
