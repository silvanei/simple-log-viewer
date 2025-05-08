<?php

declare(strict_types=1);

namespace S3\Log\Viewer\EventDispatcher;

use InvalidArgumentException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;

final class GenericEventDispatcher implements EventDispatcher
{
     /** @var array<string, array<array{object, string}>> */
    private array $handlers = [];

    public function __construct(object ...$handlers)
    {
        foreach ($handlers as $handler) {
            $this->registerHandler($handler);
        }
    }

    public function dispatch(object $event): void
    {
        foreach ($this->handlers[$event::class] ?? [] as $handlerConfig) {
            [$handler, $method] = $handlerConfig;
            $handler->{$method}($event);
        }
    }

    private function registerHandler(object $handler): void
    {
        $reflection = new ReflectionObject($handler);
        foreach ($reflection->getMethods() as $method) {
            if (! $this->isEventHandlerMethod($method)) {
                continue;
            }

            $eventType = $this->tryRetrieveEventType($method, $handler);
            $this->handlers[$eventType][] = [$handler, $method->getName()];
        }
    }

    private function isEventHandlerMethod(ReflectionMethod $method): bool
    {
        return count($method->getAttributes(EventHandler::class)) > 0;
    }

    private function tryRetrieveEventType(ReflectionMethod $method, object $handler): string
    {
        if ($method->getNumberOfParameters() !== 1) {
            throw new InvalidArgumentException(sprintf(
                'EventHandler method %s::%s must have exactly one parameter',
                $handler::class,
                $method->getName()
            ));
        }

        $parameter = $method->getParameters()[0];
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            throw new InvalidArgumentException(sprintf(
                'Parameter %s of %s::%s must be a class type',
                $parameter->getName(),
                $handler::class,
                $method->getName()
            ));
        }

        return $type->getName();
    }
}
