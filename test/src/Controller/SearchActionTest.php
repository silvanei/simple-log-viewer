<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use S3\Log\Viewer\Controller\SearchAction;
use S3\Log\Viewer\Dto\LogEntryView;
use S3\Log\Viewer\LogService;

class SearchActionTest extends TestCase
{
    /** @throws Exception */
    public function test_invoke_with_search_filter_returns_valid_response(): void
    {
        $expectedBody = <<<HTML
        <div class="wraper" role="table" aria-label="Log entries showing datetime, channel, level, and message">
            <div class="row" role="row">
                <div class="cell cell-header" role="columnheader" scope="col">&nbsp</div>
                <div class="cell cell-header" role="columnheader" scope="col"><b>Datetime</b></div>
                <div class="cell cell-header" role="columnheader" scope="col"><b>Channel</b></div>
                <div class="cell cell-header" role="columnheader" scope="col"><b>Level</b></div>
                <div class="cell cell-header" role="columnheader" scope="col"><b>Message</b></div>
                    </div>

                            <div class="row row-main" role="row">
                    <div class="cell" role="cell">
                        <button aria-label="Expand details for log entry" _="on click toggle .collapsed on next .log-content then toggle .rotate-180 on me">
                            <span class="i i-caret"></span>
                        </button>
                    </div>
                    <div class="cell datetime" role="cell"><span>2025-04-28T10:00:00Z</span></div>
                    <div class="cell channel" role="cell"><span>[a]</span></div>
                    <div class="cell level error" role="cell"><span>ERROR</span></div>
                    <div class="cell message" role="cell"><span>m1</span></div>
                            </div>
                <div class="row details log-content collapsed" role="row">
                    <div class="cell" role="cell" colspan="5">
                        <ul class="nested-list">
                    <li>
                    <span class="highlight-key">
                        <button
                            class="field-toggle-btn"
                            data-field="datetime"
                            onclick="toggleField(event, 'datetime')"
                            aria-label="Toggle column in table"
                            title="Toggle column in table"
                        >
                            <span class="i i-table"></span>
                        </button>
                        datetime            </span>
                    <span class="highlight-string">2025-04-28T10:00:00Z</span>
                </li>
                    <li>
                    <span class="highlight-key">
                        <button
                            class="field-toggle-btn"
                            data-field="channel"
                            onclick="toggleField(event, 'channel')"
                            aria-label="Toggle column in table"
                            title="Toggle column in table"
                        >
                            <span class="i i-table"></span>
                        </button>
                        channel            </span>
                    <span class="highlight-string">a</span>
                </li>
                    <li>
                    <span class="highlight-key">
                        <button
                            class="field-toggle-btn"
                            data-field="level"
                            onclick="toggleField(event, 'level')"
                            aria-label="Toggle column in table"
                            title="Toggle column in table"
                        >
                            <span class="i i-table"></span>
                        </button>
                        level            </span>
                    <span class="highlight-string">ERROR</span>
                </li>
                    <li>
                    <span class="highlight-key">
                        <button
                            class="field-toggle-btn"
                            data-field="message"
                            onclick="toggleField(event, 'message')"
                            aria-label="Toggle column in table"
                            title="Toggle column in table"
                        >
                            <span class="i i-table"></span>
                        </button>
                        message            </span>
                    <span class="highlight-string">m1</span>
                </li>
            </ul>
                    </div>
                </div>
            </div>

        HTML;

        $testFilter = 'error';
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['search' => $testFilter]);

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('search')
            ->with($testFilter)
            ->willReturn([
                new LogEntryView(datetime: '2025-04-28T10:00:00Z', channel: 'a', level: 'ERROR', message: 'm1', context: []),
            ]);

        $action = new SearchAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        $this->assertSame($expectedBody, (string) $response->getBody());
    }

    /** @throws Exception */
    public function test_invoke_with_fields_param_includes_fields_in_view(): void
    {
        $testFields = ['datetime', 'message'];
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['fields' => $testFields, 'search' => '']);

        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($this->once())
            ->method('search')
            ->with('')
            ->willReturn([]);

        $action = new SearchAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('data-field="datetime"', $body);
        $this->assertStringContainsString('data-field="message"', $body);
    }

    /** @throws Exception */
    public function test_invoke_throws_exception_on_service_failure(): void
    {
        $errorMessage = 'Database connection failed';
        $exception = new RuntimeException($errorMessage);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['search' => 'test']);

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('search')
            ->willThrowException($exception);

        $action = new SearchAction($logService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($errorMessage);

        $action->__invoke($request);
    }
}
