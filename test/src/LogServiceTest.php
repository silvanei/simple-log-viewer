<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer;

use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\Stream\ThroughStream;
use S3\Log\Viewer\EventDispatcher\Event\LogCleared;
use S3\Log\Viewer\EventDispatcher\Event\LogReceived;
use S3\Log\Viewer\EventDispatcher\Event\StreamCreated;
use S3\Log\Viewer\EventDispatcher\EventDispatcher;
use S3\Log\Viewer\LogService;
use S3\Log\Viewer\Storage\LogStorageSQLite;

class LogServiceTest extends TestCase
{
    private LogService $service;
    private EventDispatcher&MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $logStorage = new LogStorageSQLite(new PDO('sqlite::memory:'));
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
        $this->service = new LogService($logStorage, $this->eventDispatcher);
    }

    public function testCreateChannelStream_ShouldDispatchStreamCreated(): void
    {
        $stream = new ThroughStream();
        $id = 'abc123';
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(new StreamCreated($stream, $id));

        $this->service->createChannelStream($stream, $id);
    }

    public function testAdd_ShouldDispatchLogReceived(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(new LogReceived());

        $this->service->add(['datetime' => '2025-04-28T10:00:00Z','channel' => 'a','level' => 'DEBUG','message' => 'm1','context' => []]);
    }

    public function testClear_ShouldDispatchLogCleared(): void
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(new LogCleared());

        $this->service->clear();
    }

    public function testSearchWithoutFilterReturnsAll(): void
    {
        $this->service->add(['datetime' => '2025-04-28T10:00:00Z','channel' => 'a','level' => 'DEBUG','message' => 'm1','context' => ['x' => 1, 'foo' => 'bar']]);
        $this->service->add(['datetime' => '2025-04-28T11:00:00Z','channel' => 'b','level' => 'ERROR','message' => 'm2','context' => ['y' => 2, 'foo' => 'bar']]);

        $response = $this->service->search('');

        $this->assertSame(
            [
                ['datetime' => '2025-04-28T11:00:00Z','channel' => 'b','level' => 'ERROR','message' => 'm2','context' => ['y' => 2, 'foo' => 'bar'], 'extra' => []],
                ['datetime' => '2025-04-28T10:00:00Z','channel' => 'a','level' => 'DEBUG','message' => 'm1','context' => ['x' => 1, 'foo' => 'bar'], 'extra' => []],
            ],
            $response
        );
    }

    public function testSearchWithFilterMatchesOnlyRelevant(): void
    {
        $this->service->add(['datetime' => '2025-04-28T08:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foo','context' => []]);
        $this->service->add(['datetime' => '2025-04-28T09:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'bar','context' => []]);
        $this->service->add(['datetime' => '2025-04-28T10:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foobar','context' => []]);

        $response = $this->service->search('foo');

        $this->assertSame(
            [
                ['datetime' => '2025-04-28T08:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foo','context' => [], 'extra' => []],
            ],
            $response
        );
    }

    public function testClearLogsAndNotifiesChannel(): void
    {
        $this->service->add(['datetime' => '2025-04-28T08:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foo','context' => []]);
        $this->service->add(['datetime' => '2025-04-28T09:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'bar','context' => []]);

        $this->service->clear();

        $this->assertEmpty($this->service->search(''));
    }
}
