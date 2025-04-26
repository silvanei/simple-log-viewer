<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ActionHandler
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface;
}
