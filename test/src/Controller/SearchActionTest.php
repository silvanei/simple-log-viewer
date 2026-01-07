<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use S3\Log\Viewer\Controller\SearchAction;
use S3\Log\Viewer\LogService;

class SearchActionTest extends TestCase
{
    /** @throws Exception */
    public function test_invoke_with_search_filter_returns_valid_response(): void
    {
        $expectedBody = <<<HTML
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
                    <span class="datetime">2025-04-28T10:00:00Z</span>
                    <span class="channel">[a]</span>
                    <span class="level error">ERROR</span>
                    <span class="message">m1</span>
                </div>
                <div class="log-content collapsed">
                    <ul class="nested-list">
                    <li>
                    <span class="highlight-key">datetime</span>
                    <span class="highlight-string">2025-04-28T10:00:00Z</span>
                </li>
                    <li>
                    <span class="highlight-key">channel</span>
                    <span class="highlight-string">a</span>
                </li>
                    <li>
                    <span class="highlight-key">level</span>
                    <span class="highlight-string">ERROR</span>
                </li>
                    <li>
                    <span class="highlight-key">message</span>
                    <span class="highlight-string">m1</span>
                </li>
            </ul>
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
                ['datetime' => '2025-04-28T10:00:00Z','channel' => 'a','level' => 'ERROR','message' => 'm1','context' => []],
            ]);

        $action = new SearchAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        $this->assertSame($expectedBody, (string) $response->getBody());
    }

    /** @throws Exception */
    public function test_invoke_returns_400_on_service_failure(): void
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
        $response = $action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
        $this->assertSame($errorMessage, (string) $response->getBody());
    }
}
