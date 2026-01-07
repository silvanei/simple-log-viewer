<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use FastRoute\RouteCollector;
use React\Http\HttpServer;
use React\Socket\SocketServer;
use S3\Log\Viewer\Handler\RouterHandler;
use S3\Log\Viewer\Middleware\StaticFileMiddleware;
use S3\Log\Viewer\Middleware\GzipMiddleware;

use function FastRoute\simpleDispatcher;

final class Application
{
    /** @var array<string, array{method: string, handler: ActionHandler}>  */
    private array $routes;

    public function get(string $route, ActionHandler $handler): void
    {
        $this->routes[$route] = ['method' => 'GET', 'handler' => $handler];
    }

    public function post(string $path, ActionHandler $action): void
    {
        $this->routes[$path] = ['method' => 'POST', 'handler' => $action];
    }

    public function listen(string $uri): void
    {
        $dispatcher = simpleDispatcher(function (RouteCollector $router): void {
            foreach ($this->routes as $route => ['method' => $method, 'handler' => $handler]) {
                $router->addRoute($method, $route, $handler);
            }
        });

        $server = new HttpServer(
            new GzipMiddleware(),
            new StaticFileMiddleware('public'),
            new RouterHandler($dispatcher),
        );
        $server->listen(new SocketServer($uri));

        echo 'Server running at ', $uri, PHP_EOL;
    }
}
