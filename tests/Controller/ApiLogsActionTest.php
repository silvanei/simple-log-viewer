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
        'datetime' => '2025-05-04T12:00:00+00:00',
        'channel' => 'channel',
        'level' => 'info',
        'message' => 'test',
        'context' => []
    ];

    /** @throws Exception */
    public function testInvokeWithValidDataReturns201Response(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
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
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
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
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
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

    /** @throws Exception */
    public function testInvokeWithUnsupportedMediaTypeReturns415Response(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('text/plain');

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->never())->method('add');

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(415, $response->getStatusCode());
        $this->assertSame('Unsupported Media Type', (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    /** @throws Exception */
    public function testInvokeWithValidationErrorsReturns400Response(): void
    {
        $invalidData = [
            'datetime' => 'invalid-datetime',
            'channel' => 'ch',
            'level' => 'invalid-level',
            'message' => 'te',
        ];

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->never())->method('add');

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Invalid or missing datetime', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing channel', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing level', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing message', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing context', (string) $response->getBody());
    }

    /** @throws Exception */
    public function testInvokeWithInvalidChannelLengthReturns400Response(): void
    {
        $invalidData = self::VALID_DATA;
        $invalidData['channel'] = str_repeat('a', 256);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('add');
        $action = new ApiLogsAction($logService);

        $response = $action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or missing channel', (string) $response->getBody());

        $invalidData['channel'] = str_repeat('a', 255);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $response = $action->__invoke($request);

        $this->assertSame(201, $response->getStatusCode());
    }

    /** @throws Exception */
    public function testInvokeWithInvalidMessageLengthReturns400Response(): void
    {
        $invalidData = self::VALID_DATA;
        $invalidData['message'] = 'ab';

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('add');

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or missing message', (string) $response->getBody());

        $invalidData['message'] = str_repeat('a', 256); // Exceeds maximum length

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or missing message', (string) $response->getBody());

        $invalidData['message'] = 'abc';
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(201, $response->getStatusCode());
    }

    /** @throws Exception */
    public function testInvokeWithChannelLengthEdgeCases(): void
    {
        $invalidData = self::VALID_DATA;
        $invalidData['channel'] = 'abc'; // Minimum valid length

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->exactly(1))->method('add');

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(201, $response->getStatusCode());

        $invalidData['channel'] = 'ab'; // Invalid length
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $response = $action->__invoke($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or missing channel', (string) $response->getBody());

        $invalidData['channel'] = str_repeat('a', 256); // Exceeds maximum length
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $response = $action->__invoke($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or missing channel', (string) $response->getBody());
    }

    /** @throws Exception */
    public function testInvokeWithLevelEdgeCases(): void
    {
        $invalidData = self::VALID_DATA;
        $invalidData['level'] = 'debug'; // Valid level but lowercase

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->exactly(1))->method('add');

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(201, $response->getStatusCode());

        $invalidData['level'] = 'invalid'; // Invalid level
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $response = $action->__invoke($request);
        $this->assertSame(400, $response->getStatusCode()); // Adjusted to correctly validate the invalid level case
        $this->assertStringContainsString('Invalid or missing level', (string) $response->getBody());
    }

    /** @throws Exception */
    public function testInvokeWithMessageLengthEdgeCases(): void
    {
        $invalidData = self::VALID_DATA;
        $invalidData['message'] = str_repeat('a', 255); // Maximum valid length

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->exactly(1))->method('add');

        $action = new ApiLogsAction($logService);
        $response = $action->__invoke($request);

        $this->assertSame(201, $response->getStatusCode());

        $invalidData['message'] = ''; // Invalid length
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $request->method('getBody')->willReturn(json_encode($invalidData));

        $response = $action->__invoke($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid or missing message', (string) $response->getBody());
    }
}
