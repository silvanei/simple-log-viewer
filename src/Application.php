<?php

declare(strict_types=1);

namespace S3\Log\Viewer;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use S3\Log\Viewer\Handler\RouterHandler;

use function FastRoute\simpleDispatcher;

final class Application implements RequestHandlerInterface
{
    /** @var array<string, array{method: string, handler: ActionHandler}>  */
    private array $routes = [];

    private ?Dispatcher $dispatcher = null;

    public function get(string $route, ActionHandler $handler): void
    {
        $this->routes[$route] = ['method' => 'GET', 'handler' => $handler];
    }

    public function post(string $path, ActionHandler $action): void
    {
        $this->routes[$path] = ['method' => 'POST', 'handler' => $action];
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = simpleDispatcher(function (RouteCollector $router): void {
                foreach ($this->routes as $route => ['method' => $method, 'handler' => $handler]) {
                    $router->addRoute($method, $route, $handler);
                }
            });
        }

        $routerHandler = new RouterHandler($this->dispatcher);

        return $routerHandler($request);
    }
}
