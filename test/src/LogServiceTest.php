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
        $this->service->add([
            'datetime' => '2025-05-17T16:39:03+00:00',
            'channel' => 'test',
            'level' => 'DEBUG',
            'message' => 'hello',
            'context' => ['num' => 123, 'str' => 'ok', 'str_nl' => "foo\nbar", 'bool' => false, 'nul' => null, 'array' => ['num' => 123, 'str' => 'ok'], 'list' => ['a', 'b', 'c']],
            'extra' => ['memory_peak_usage' => '4 MB']
        ]);

        $html = $this->service->search('');


        $this->assertEquals(
            <<<HTML
            <div class="log-entry">
                <div class="log-header" _="on click toggle .collapsed on next .log-content then toggle .rotate-180 on first in me">
                    <button>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" width="16">
                            <path
                                fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                    <span class="datetime">2025-05-17T16:39:03+00:00</span>
                    <span class="channel">[test]</span>
                    <span class="level debug">debug</span>
                    <span class="message">hello</span>
                </div>
                <div class="log-content collapsed" data-json="eyJkYXRldGltZSI6IjIwMjUtMDUtMTdUMTY6Mzk6MDMrMDA6MDAiLCJjaGFubmVsIjoidGVzdCIsImxldmVsIjoiZGVidWciLCJtZXNzYWdlIjoiaGVsbG8iLCJjb250ZXh0Ijp7Im51bSI6MTIzLCJzdHIiOiJvayIsInN0cl9ubCI6ImZvb1xuYmFyIiwiYm9vbCI6ZmFsc2UsIm51bCI6bnVsbCwiYXJyYXkiOnsibnVtIjoxMjMsInN0ciI6Im9rIn0sImxpc3QiOlsiYSIsImIiLCJjIl19LCJleHRyYSI6eyJtZW1vcnlfcGVha191c2FnZSI6IjQgTUIifX0=">
                    <ul><li class="tree-item"><span class="highlight-key">datetime</span>: <span class="highlight-string">"2025-05-17T16:39:03+00:00"</span></li>
            <li class="tree-item"><span class="highlight-key">channel</span>: <span class="highlight-string">"test"</span></li>
            <li class="tree-item"><span class="highlight-key">level</span>: <span class="highlight-string">"debug"</span></li>
            <li class="tree-item"><span class="highlight-key">message</span>: <span class="highlight-string">"hello"</span></li>
            <li class="tree-item"><span class="highlight-key">context</span>: <button class="toggle-btn" _="on click toggle .highlight-toggle-display on next .highlight-toggle then toggle .rotate-180 on first in me">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 icon-toggle" viewBox="0 0 20 20" fill="currentColor" width="16">
                    <path
                        fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <ul class="nested-list highlight-toggle-display highlight-toggle"><li class="tree-item"><span class="highlight-key">num</span>: <span class="highlight-number">123</span></li>
            <li class="tree-item"><span class="highlight-key">str</span>: <span class="highlight-string">"ok"</span></li>
            <li class="tree-item"><span class="highlight-key">str_nl</span>: <span class="highlight-string">"foo
              bar"</span></li>
            <li class="tree-item"><span class="highlight-key">bool</span>: <span class="highlight-boolean">false</span></li>
            <li class="tree-item"><span class="highlight-key">nul</span>: <span class="highlight-null">null</span></li>
            <li class="tree-item"><span class="highlight-key">array</span>: <button class="toggle-btn" _="on click toggle .highlight-toggle-display on next .highlight-toggle then toggle .rotate-180 on first in me">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 icon-toggle" viewBox="0 0 20 20" fill="currentColor" width="16">
                    <path
                        fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <ul class="nested-list highlight-toggle-display highlight-toggle"><li class="tree-item"><span class="highlight-key">num</span>: <span class="highlight-number">123</span></li>
            <li class="tree-item"><span class="highlight-key">str</span>: <span class="highlight-string">"ok"</span></li>
            </ul></li>
            <li class="tree-item"><span class="highlight-key">list</span>: <button class="toggle-btn" _="on click toggle .highlight-toggle-display on next .highlight-toggle then toggle .rotate-180 on first in me">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 icon-toggle" viewBox="0 0 20 20" fill="currentColor" width="16">
                    <path
                        fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <ul class="nested-list highlight-toggle-display highlight-toggle"><li class="list-item"><span class="highlight-string">"a"</span></li>
            <li class="list-item"><span class="highlight-string">"b"</span></li>
            <li class="list-item"><span class="highlight-string">"c"</span></li>
            </ul></li>
            </ul></li>
            <li class="tree-item"><span class="highlight-key">extra</span>: <button class="toggle-btn" _="on click toggle .highlight-toggle-display on next .highlight-toggle then toggle .rotate-180 on first in me">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 icon-toggle" viewBox="0 0 20 20" fill="currentColor" width="16">
                    <path
                        fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <ul class="nested-list highlight-toggle-display highlight-toggle"><li class="tree-item"><span class="highlight-key">memory_peak_usage</span>: <span class="highlight-string">"4 MB"</span></li>
            </ul></li>
            </ul>
                    <div class="log-actions">
                        <button class="toggle-highlight-btn" _="on click toggleHighlight(event)">
                            <svg class="toggle-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Expand All</span>
                        </button>
                        <button class="copy-json-btn" _="on click copyJSON(event)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            </svg>
                            Copy JSON
                        </button>
                    </div>
                </div>
            </div>
            HTML,
            $html
        );
    }

    public function testRenderLogLowercasesLevel(): void
    {
        $this->service->add(['datetime' => '2025-04-28T14:00:00Z', 'channel' => 'test', 'level' => 'ERROR', 'message' => 'oops', 'context' => []]);

        $html = $this->service->search('');

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
