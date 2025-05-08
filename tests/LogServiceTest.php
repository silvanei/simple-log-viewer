<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer;

use PDO;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\EventDispatcher\EventDispatcher;
use S3\Log\Viewer\LogService;
use S3\Log\Viewer\Storage\LogStorageSQLite;

class LogServiceTest extends TestCase
{
    private LogService $service;

    protected function setUp(): void
    {
        $logStorage = new LogStorageSQLite(new PDO('sqlite::memory:'));
        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->service = new LogService($logStorage, $eventDispatcher);
    }

    public function testSearchWithoutFilterReturnsAll(): void
    {
        $this->service->add(['datetime' => '2025-04-28T10:00:00Z','channel' => 'a','level' => 'DEBUG','message' => 'm1','context' => ['x' => 1]]);
        $this->service->add(['datetime' => '2025-04-28T11:00:00Z','channel' => 'b','level' => 'ERROR','message' => 'm2','context' => ['y' => 2]]);

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

        $line = ['datetime' => '2025-04-28T13:00:00Z','channel' => 'test', 'level' => 'WARN', 'message' => 'hello', 'context' => '{"num":123,"str":"ok","bool":false,"nul":null}'];
        $html = $method->invoke($this->service, $line);

        $this->assertStringContainsString(' <span class="highlight-key">num</span>: <span class="highlight-number">123</span>', $html);
        $this->assertStringContainsString(' <span class="highlight-key">str</span>: <span class="highlight-string">"ok"</span>', $html);
        $this->assertStringContainsString(' <span class="highlight-key">bool</span>: <span class="highlight-boolean">false</span>', $html);
        $this->assertStringContainsString(' <span class="highlight-key">nul</span>: <span class="highlight-null">null</span>', $html);
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

    public function testClearLogsAndNotifiesChannel(): void
    {
        $this->service->add(['datetime' => '2025-04-28T08:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'foo','context' => []]);
        $this->service->add(['datetime' => '2025-04-28T09:00:00Z','channel' => 'ch','level' => 'INFO','message' => 'bar','context' => []]);

        $this->service->clear();

        $this->assertEmpty($this->service->search(''));
    }
}
