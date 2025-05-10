<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\EventDispatcher;

use Generator;
use InvalidArgumentException;
use JsonSerializable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\EventDispatcher\EventHandler;
use S3\Log\Viewer\EventDispatcher\GenericEventDispatcher;
use stdClass;
use Test\S3\Log\Viewer\support\EventDispatcher\StdClassEventHandler;

class GenericEventDispatcherTest extends TestCase
{
    public function testDispatch_ShouldCallRegisteredHandler(): void
    {
        $handler = $this->createMock(StdClassEventHandler::class);
        $handler
            ->expects($this::once())
            ->method('handleStdClassEvent')
            ->with($event = new stdClass());

        $dispatcher = new GenericEventDispatcher($this->withDecorator($handler));
        $dispatcher->dispatch($event);
    }

    public function testDispatch_ShouldCallMultipleRegisteredHandler(): void
    {
        $event = new stdClass();

        $handler1 = $this->createMock(StdClassEventHandler::class);
        $handler1
            ->expects($this::once())
            ->method('handleStdClassEvent')
            ->with($event);

        $handler2 = $this->createMock(StdClassEventHandler::class);
        $handler2
            ->expects($this::once())
            ->method('handleStdClassEvent')
            ->with($event);

        $dispatcher = new GenericEventDispatcher(
            $this->withDecorator($handler1),
            $this->withDecorator($handler2)
        );
        $dispatcher->dispatch($event);
    }

    public function testDispatch_ShouldNotCallRegisteredHandler_WhenNotContainEventHandlerAttribute(): void
    {
        $handler = $this->createMock(StdClassEventHandler::class);
        $handler
            ->expects($this::never())
            ->method('handleStdClassEvent');

        $dispatcher = new GenericEventDispatcher($handler);
        $dispatcher->dispatch(new stdClass());
    }

    public static function invalidMethodProvider(): Generator
    {
        yield 'without params' => [
            'handler' => new class {
                #[EventHandler]
                public function invalid(): void
                {
                }
            },
            'expectedExceptionMessage' => 'must have exactly one parameter'
        ];
        yield 'with multiple param' => [
            'handler' => new class {
                #[EventHandler]
                public function invalid(stdClass $event1, stdClass $event2): void
                {
                }
            },
            'expectedExceptionMessage' => 'must have exactly one parameter'
        ];
        yield 'with builtin param' => [
            'handler' => new class {
                #[EventHandler]
                public function invalid(int $event): void
                {
                }
            },
            'expectedExceptionMessage' => 'must be a class type'
        ];
        yield 'with multiple types' => [
            'handler' => new class {
                #[EventHandler]
                public function invalid(stdClass|JsonSerializable $event): void
                {
                }
            },
            'expectedExceptionMessage' => 'must be a class type'
        ];
    }

    #[DataProvider('invalidMethodProvider')]
    public function testRegisterHandler_ShouldThrowsOnInvalidMethods(object $handler, string $expectedExceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        new GenericEventDispatcher($handler);
    }

    private function withDecorator(StdClassEventHandler $handler): StdClassEventHandler
    {
        return new class ($handler) extends StdClassEventHandler {
            public function __construct(private readonly StdClassEventHandler $handler)
            {
            }

            #[EventHandler]
            public function handleStdClassEvent(stdClass $event): void
            {
                $this->handler->handleStdClassEvent($event);
            }
        };
    }
}
