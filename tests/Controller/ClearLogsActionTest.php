<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use S3\Log\Viewer\Controller\ClearLogsAction;
use S3\Log\Viewer\LogService;

class ClearLogsActionTest extends TestCase
{
    public function testInvokeReturns200OnSuccess(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('clear');

        $action = new ClearLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
        $this->assertEmpty((string)$response->getBody());
    }

    public function testInvokeReturns500OnError(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $logService = $this->createMock(LogService::class);
        $logService->method('clear')->willThrowException(new RuntimeException('Database error'));

        $action = new ClearLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
        $this->assertSame('Database error', (string)$response->getBody());
    }
}
