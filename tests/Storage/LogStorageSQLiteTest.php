<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Storage;

use PDO;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\Storage\LogStorage;
use S3\Log\Viewer\Storage\LogStorageSQLite;

class LogStorageSQLiteTest extends TestCase
{
    private PDO $pdo;
    private LogStorage $logStorageSQLite;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->logStorageSQLite = new LogStorageSQLite($this->pdo);
    }

    /** @throws Exception */
    public function testConstructor_ShouldSetPDOErrorModToException(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('setAttribute')->with(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        new LogStorageSQLite($pdo);
    }

    /** @throws Exception */
    public function testConstructor_ShouldCreatesFts5Table(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo
            ->expects($matcher = $this->exactly(2))
            ->method('exec')
            ->with($this->callback(function (string $statement) use ($matcher): bool {
                $expectedStatement = match ($matcher->numberOfInvocations()) {
                    1 => 'PRAGMA foreign_keys = ON',
                    2 => <<<SQL
                        CREATE VIRTUAL TABLE IF NOT EXISTS logs USING fts5(
                            datetime,
                            channel,
                            level,
                            message,
                            context
                        );
                        SQL,
                    default => 'Not mapped statement',
                };

                $this->assertSame($expectedStatement, $statement);

                return true;
            }));

        new LogStorageSQLite($pdo);
    }

    public function testAdd_ShouldInsertLog(): void
    {
        $this->logStorageSQLite->add(['datetime' => '2025-04-28T12:00:00Z', 'channel' => 'app', 'level' => 'INFO', 'message' => 'Test message', 'context' => ['foo' => 'bar']]);

        /** @var array{datetime: string, channel: string, level: string, message: string, context: array<string|int, mixed>} $row */
        $row = $this->pdo->query("SELECT datetime, channel, level, message, context FROM logs")->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('2025-04-28T12:00:00Z', $row['datetime']);
        $this->assertSame('app', $row['channel']);
        $this->assertSame('INFO', $row['level']);
        $this->assertSame('Test message', $row['message']);
        $this->assertSame(json_encode(['foo' => 'bar'], JSON_UNESCAPED_UNICODE), $row['context']);
    }

    public function testSearch_ShouldReturnAllLogsSortedByDatetimeDesc_WhenFilterIsEmpty(): void
    {
        $this->logStorageSQLite->add(['datetime' => '2025-04-28T10:00:00Z','channel' => 'a','level' => 'DEBUG','message' => 'm1','context' => ['x' => 1]]);
        $this->logStorageSQLite->add(['datetime' => '2025-04-28T11:00:00Z','channel' => 'b','level' => 'ERROR','message' => 'm2','context' => ['y' => 2]]);

        $data = $this->logStorageSQLite->search('');

        $this->assertCount(2, $data);
        $this->assertSame('2025-04-28T11:00:00Z', $data[0]['datetime']);
        $this->assertSame('b', $data[0]['channel']);
        $this->assertSame('ERROR', $data[0]['level']);
        $this->assertSame('m2', $data[0]['message']);
        $this->assertSame(json_encode(['y' => 2], JSON_UNESCAPED_UNICODE), $data[0]['context']);

        $this->assertSame('2025-04-28T10:00:00Z', $data[1]['datetime']);
        $this->assertSame('a', $data[1]['channel']);
        $this->assertSame('DEBUG', $data[1]['level']);
        $this->assertSame('m1', $data[1]['message']);
        $this->assertSame(json_encode(['x' => 1], JSON_UNESCAPED_UNICODE), $data[1]['context']);
    }

    public function testSearch_ShouldReturnMatchesOnlyRelevant_WhenFiltered(): void
    {
        $this->logStorageSQLite->add(['datetime' => '2025-04-28T08:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foo','context' => []]);
        $this->logStorageSQLite->add(['datetime' => '2025-04-28T09:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'bar','context' => []]);
        $this->logStorageSQLite->add(['datetime' => '2025-04-28T10:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foobar','context' => []]);

        $data = $this->logStorageSQLite->search('foo');

        $this->assertCount(1, $data);
        $this->assertSame('2025-04-28T08:00:00Z', $data[0]['datetime']);
        $this->assertSame('ch', $data[0]['channel']);
        $this->assertSame('INFO', $data[0]['level']);
        $this->assertSame('foo', $data[0]['message']);
        $this->assertSame(json_encode([], JSON_UNESCAPED_UNICODE), $data[0]['context']);
    }
}
