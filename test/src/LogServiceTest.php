<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer;

use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\Stream\ThroughStream;
use S3\Log\Viewer\Dto\LogEntry;
use S3\Log\Viewer\Dto\LogEntryView;
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

        $this->service->add(new LogEntry('2025-04-28T10:00:00Z', 'a', 'DEBUG', 'm1', []));
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
        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch');
        $this->service->add(new LogEntry('2025-04-28T10:00:00Z', 'a', 'DEBUG', 'm1', ['x' => 1, 'foo' => 'bar']));
        $this->service->add(new LogEntry('2025-04-28T11:00:00Z', 'b', 'ERROR', 'm2', ['y' => 2, 'foo' => 'bar']));

        $response = $this->service->search('');

        $this->assertEquals(
            [
                new LogEntryView('2025-04-28T11:00:00Z', 'b', 'ERROR', 'm2', ['y' => '2', 'foo' => 'bar'], []),
                new LogEntryView('2025-04-28T10:00:00Z', 'a', 'DEBUG', 'm1', ['x' => '1', 'foo' => 'bar'], []),
            ],
            $response
        );
    }

    public function testSearchWithFilterMatchesOnlyRelevant(): void
    {
        $this->eventDispatcher->expects($this->exactly(3))->method('dispatch');
        $this->service->add(new LogEntry('2025-04-28T08:00:00Z', 'ch', 'INFO', 'foo', []));
        $this->service->add(new LogEntry('2025-04-28T09:00:00Z', 'ch', 'INFO', 'bar', []));
        $this->service->add(new LogEntry('2025-04-28T10:00:00Z', 'ch', 'INFO', 'foobar', []));

        $response = $this->service->search('foo');

        $this->assertEquals(
            [
                new LogEntryView('2025-04-28T08:00:00Z', 'ch', 'INFO', '⟦foo⟧', [], []),
            ],
            $response
        );
    }

    public function testClearLogsAndNotifiesChannel(): void
    {
        $this->eventDispatcher->expects($this->exactly(3))->method('dispatch');
        $this->service->add(new LogEntry('2025-04-28T08:00:00Z', 'ch', 'INFO', 'foo', []));
        $this->service->add(new LogEntry('2025-04-28T09:00:00Z', 'ch', 'INFO', 'bar', []));

        $this->service->clear();

        $this->assertEmpty($this->service->search(''));
    }
}
