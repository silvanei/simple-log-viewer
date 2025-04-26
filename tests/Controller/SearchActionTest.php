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
        $mockHtml = '<div>Results</div>';
        $testFilter = 'error';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['search' => $testFilter]);

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('search')
            ->with($testFilter)
            ->willReturn($mockHtml);

        $action = new SearchAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
        $this->assertSame($mockHtml, (string) $response->getBody());
    }

    /** @throws Exception */
    public function test_invoke_without_search_parameter_uses_empty_filter(): void
    {
        $mockHtml = '<div>All logs</div>';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('search')
            ->with('')
            ->willReturn($mockHtml);

        $action = new SearchAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($mockHtml, (string) $response->getBody());
    }

    /** @throws Exception */
    public function test_invoke_returns_400_on_service_failure(): void
    {
        $errorMessage = 'Database connection failed';
        $exception = new RuntimeException($errorMessage);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['search' => 'test']);

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
