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

        $body = (string) $response->getBody();

        $this->assertStringContainsString('<div class="wraper" role="table"', $body);
        $this->assertStringContainsString('Showing 1 log entries', $body);
        $this->assertStringContainsString('aria-live="polite"', $body);
        $this->assertStringContainsString('aria-atomic="true"', $body);

        $this->assertStringContainsString('Datetime', $body);
        $this->assertStringContainsString('Channel', $body);
        $this->assertStringContainsString('Level', $body);
        $this->assertStringContainsString('Message', $body);

        $this->assertStringContainsString('2025-04-28T10:00:00Z', $body);
        $this->assertStringContainsString('[a]', $body);
        $this->assertStringContainsString('ERROR', $body);
        $this->assertStringContainsString('m1', $body);

        $this->assertStringContainsString('field-toggle-btn', $body);
        $this->assertStringContainsString('aria-label="Toggle column"', $body);

        $this->assertStringContainsString('aria-label="Expand"', $body);
        $this->assertStringContainsString('aria-expanded="false"', $body);

        $this->assertStringContainsString('i-table', $body);
        $this->assertStringContainsString('i-error', $body);
        $this->assertStringContainsString('i-caret', $body);

        $this->assertSame(1, substr_count($body, 'row-main'));
        $this->assertGreaterThanOrEqual(4, substr_count($body, 'field-toggle-btn'));
        $this->assertSame(4, substr_count($body, 'i-table'));
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
    public function test_invoke_with_fields_param_shows_move_buttons(): void
    {
        $testFields = ['datetime', 'message', 'custom'];
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['fields' => $testFields, 'search' => 'test']);

        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($this->once())
            ->method('search')
            ->with('test')
            ->willReturn([
                new LogEntryView(
                    datetime: '2025-04-28T10:00:00Z',
                    channel: 'app',
                    level: 'ERROR',
                    message: 'Test message',
                    context: [],
                ),
            ]);

        $action = new SearchAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        $this->assertStringContainsString('class="field-actions"', $body);
        $this->assertStringContainsString('i-chevron-left', $body);
        $this->assertStringContainsString('i-chevron-right', $body);

        $this->assertSame(2, substr_count($body, 'aria-label="Move column left"'));
        $this->assertSame(2, substr_count($body, 'aria-label="Move column right"'));
        $this->assertSame(3, substr_count($body, 'aria-label="Remove column"'));

        $this->assertStringContainsString('data-field="datetime"', $body);
        $this->assertStringContainsString('data-field="message"', $body);
        $this->assertStringContainsString('data-field="custom"', $body);

        $this->assertStringContainsString('sr-only', $body);
        $this->assertStringContainsString('Move column left', $body);
        $this->assertStringContainsString('Move column right', $body);
    }

    /** @throws Exception */
    public function test_invoke_with_single_field_hides_move_buttons(): void
    {
        $testFields = ['datetime'];
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['fields' => $testFields, 'search' => 'test']);

        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($this->once())
            ->method('search')
            ->with('test')
            ->willReturn([
                new LogEntryView(
                    datetime: '2025-04-28T10:00:00Z',
                    channel: 'app',
                    level: 'ERROR',
                    message: 'Test message',
                    context: [],
                ),
            ]);

        $action = new SearchAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();

        $this->assertSame(0, substr_count($body, 'aria-label="Move column left"'));
        $this->assertSame(0, substr_count($body, 'aria-label="Move column right"'));
        $this->assertSame(1, substr_count($body, 'aria-label="Remove column"'));
        $this->assertStringContainsString('data-field="datetime"', $body);
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
