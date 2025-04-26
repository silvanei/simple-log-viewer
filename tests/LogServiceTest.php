<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer;

use Clue\React\Sse\BufferedChannel;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Stream\ThroughStream;
use S3\Log\Viewer\LogService;

class LogServiceTest extends TestCase
{
    private PDO $pdo;
    private BufferedChannel&MockObject $channelMock;
    private LogService $service;

    protected function setUp(): void
    {
        // Stub de Loop que executa callbacks imediatamente
        $loopMock = $this->createMock(LoopInterface::class);
        $loopMock->method('futureTick')->willReturnCallback(fn(callable $cb) => $cb());
        Loop::set($loopMock);

        // PDO in-memory e mock do canal SSE
        $this->pdo = new PDO('sqlite::memory:');
        $this->channelMock = $this->createMock(BufferedChannel::class);

        $this->service = new LogService($this->pdo, $this->channelMock);
    }

    public function testConstructorCreatesFts5Table(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info('logs')");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

        $this->assertContains('datetime', $columns);
        $this->assertContains('channel', $columns);
        $this->assertContains('level', $columns);
        $this->assertContains('message', $columns);
        $this->assertContains('context', $columns);
    }

    public function testConstructorSetsErrModeAndForeignKeys(): void
    {
        $errMode = $this->pdo->getAttribute(PDO::ATTR_ERRMODE);
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $errMode, 'Errmode should be ERRMODE_EXCEPTION');

        $foreign = $this->pdo->query('PRAGMA foreign_keys')->fetchColumn();
        $this->assertEquals('1', (string)$foreign, 'Foreign keys should be enabled');
    }

    public function testAddInsertsAndEmitsMessage(): void
    {
        $log = [
            'datetime' => '2025-04-28T12:00:00Z',
            'channel'  => 'app',
            'level'    => 'INFO',
            'message'  => 'Test message',
            'context'  => ['foo' => 'bar'],
        ];

        $this->channelMock
            ->expects($this->once())
            ->method('writeMessage')
            ->with('Received new log');

        $this->service->add($log);

        $row = $this->pdo->query("SELECT datetime, channel, level, message, context FROM logs")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame($log['datetime'], $row['datetime']);
        $this->assertSame($log['channel'], $row['channel']);
        $this->assertSame($log['level'], $row['level']);
        $this->assertSame($log['message'], $row['message']);
        $this->assertSame(json_encode($log['context'], JSON_UNESCAPED_UNICODE), $row['context']);
    }

    public function testSearchWithoutFilterReturnsAll(): void
    {
        $entries = [
            ['datetime' => '2025-04-28T10:00:00Z','channel' => 'a','level' => 'DEBUG','message' => 'm1','context' => '{"x":1}'],
            ['datetime' => '2025-04-28T11:00:00Z','channel' => 'b','level' => 'ERROR','message' => 'm2','context' => '{"y":2}'],
        ];
        $stmt = $this->pdo->prepare(
            "INSERT INTO logs (datetime, channel, level, message, context) VALUES (:datetime,:channel,:level,:message,:context)"
        );
        foreach ($entries as $e) {
            $stmt->execute($e);
        }

        $html = $this->service->search('');

        $this->assertStringContainsString('m2', $html);
        $this->assertStringContainsString('m1', $html);
        $this->assertTrue(strpos($html, 'm2') < strpos($html, 'm1'));
    }

    public function testSearchWithFilterMatchesOnlyRelevant(): void
    {
        $this->service->add(['datetime' => '2025-04-28T08:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foo','context' => []]);
        $this->service->add(['datetime' => '2025-04-28T09:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'bar','context' => []]);
        $this->service->add(['datetime' => '2025-04-28T10:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foobar','context' => []]);

        $html = $this->service->search('foo');

        $this->assertStringContainsString('<span class="message">foo</span>', $html);
        $this->assertStringNotContainsString('<span class="message">foobar</span>', $html);
        $this->assertStringNotContainsString('<span class="message">bar</span>', $html);
    }

    public function testRenderLogOutputsHtmlWithHighlightedJson(): void
    {
        $ref = new \ReflectionClass(LogService::class);
        $method = $ref->getMethod('renderLog');
        $method->setAccessible(true);

        $line = ['datetime' => '2025-04-28T13:00:00Z','channel' => 'test','level' => 'WARN','message' => 'hello','context' => '{"num":123,"str":"ok","bool":false,"nul":null}'];
        $html = $method->invoke($this->service, $line);

        $this->assertStringContainsString(' <span class="json-key">"num"</span>: <span class="json-number">123</span>', $html);
        $this->assertStringContainsString(' <span class="json-key">"str"</span>: <span class="json-string">"ok"</span>', $html);
        $this->assertStringContainsString(' <span class="json-key">"bool"</span>: <span class="json-boolean">false</span>', $html);
        $this->assertStringContainsString(' <span class="json-key">"nul"</span>: <span class="json-null">null</span>', $html);
    }

    public function testRenderLogLowercasesLevel(): void
    {
        $ref = new \ReflectionClass(LogService::class);
        $method = $ref->getMethod('renderLog');
        $method->setAccessible(true);

        $line = ['datetime' => '2025-04-28T14:00:00Z','channel' => 'test','level' => 'ERROR','message' => 'oops','context' => '{}'];
        $html = $method->invoke($this->service, $line);

        $this->assertStringContainsString('<span class="level error">error</span>', $html);
    }

    public function testChannelConnectsAndDisconnects(): void
    {
        $stream = new ThroughStream();
        $id = 'abc123';

        $this->channelMock->expects($this->once())->method('connect')->with($stream, $id);
        $this->channelMock->expects($this->once())->method('disconnect')->with($stream);

        $this->service->channel($stream, $id);
        $stream->emit('close', []);
    }

    public function testHighlightJsonHandlesEncodeException(): void
    {
        $ref = new \ReflectionClass(LogService::class);
        $method = $ref->getMethod('highlightJson');
        $method->setAccessible(true);

        $decoded = ['bad' => "\xB1\x31"];
        $html = $method->invoke($this->service, $decoded);

        $this->assertEquals('<span class="json-error">Error encoding JSON: Malformed UTF-8 characters, possibly incorrectly encoded</span>', $html);
    }
}
