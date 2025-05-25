<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use S3\Log\Viewer\ActionHandler;
use S3\Log\Viewer\View\GenericViewModel;

readonly class HomeAction implements ActionHandler
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $view = new GenericViewModel(
            template: 'home/search-container',
            params: ['title' => 'Log Viewer', 'header' => 'Real-time Log Viewer'],
            extends: 'layout'
        );

        return Response::html($view->render());
    }
}
