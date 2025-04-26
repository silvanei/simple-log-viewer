<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use S3\Log\Viewer\Controller\ApiLogsAction;
use S3\Log\Viewer\LogService;

class ApiLogsActionTest extends TestCase
{
    private const array VALID_DATA = [
        'datetime' => '2023-10-01',
        'channel' => 'channel',
        'level' => 'info',
        'message' => 'test',
        'context' => '{}'
    ];

    /** @throws Exception */
    public function testInvokeWithValidDataReturns201Response(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn(json_encode(self::VALID_DATA));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('add')
            ->with(self::VALID_DATA);

        $response = (new ApiLogsAction($logService))($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Received log', (string) $response->getBody());
        $this->assertEmpty($response->getHeader('Content-Type'));
    }

    /** @throws Exception */
    public function testInvokeWithInvalidJsonReturns400Response(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn('invalid json');

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->never())->method('add');

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertEquals('Syntax error', (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    /** @throws Exception */
    public function testInvokeWhenLogServiceThrowsExceptionReturns400(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn(json_encode(self::VALID_DATA));

        $exception = new \RuntimeException('Service unavailable');

        $logService = $this->createMock(LogService::class);
        $logService->method('add')->willThrowException($exception);

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame($exception->getMessage(), (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }
}
