<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
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
        'context' => [],
    ];

    public function testWithValidDataReturns201Response(): void
    {
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(self::VALID_DATA));
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('add')
            ->with(self::VALID_DATA);

        $response = $this->executeAction($logService, $request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Received log', (string) $response->getBody());
        $this->assertEmpty($response->getHeader('Content-Type'));
    }

    public function testInvokeWithInvalidJsonReturns400Response(): void
    {
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn('invalid json');
        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertEquals('Syntax error', (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    public function testInvokeWhenLogServiceThrowsExceptionReturns400(): void
    {
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(self::VALID_DATA));
        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($this->once())
            ->method('add')
            ->willThrowException(new \RuntimeException($expectedMessage = 'Service unavailable'));

        $response = $this->executeAction($logService, $request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame($expectedMessage, (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    /** @throws Exception */
    public function testInvokeWithUnsupportedMediaTypeReturns415Response(): void
    {
        $logService = $this->createStub(LogService::class);
        $request = $this->createRequestMock(contentType: 'text/plain');

        $response = $this->executeAction($logService, $request);

        $this->assertSame(415, $response->getStatusCode());
        $this->assertSame('Unsupported Media Type', (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    public function testInvokeWithMissingRequiredFieldsReturns400Response(): void
    {
        $invalidData = [];
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Invalid or missing datetime', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing channel', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing level', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing message', (string) $response->getBody());
        $this->assertStringContainsString('Invalid or missing context', (string) $response->getBody());
    }

    public static function channelDataProvider(): Generator
    {
        yield 'Before min length' => [
            'channel' => str_repeat('a', 2),
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing channel'
        ];
        yield 'Min length' => [
            'channel' => str_repeat('a', 3),
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Max length' => [
            'channel' => str_repeat('a', 255),
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'After max length' => [
            'channel' => str_repeat('a', 256),
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing channel'
        ];
    }

    #[DataProvider('channelDataProvider')]
    public function testInvokeWithChannelLength(string $channel, int $expectedStatusCode, string $expectedResponseBody): void
    {
        $invalidData = [...self::VALID_DATA, ...['channel' => $channel]];
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertStringContainsString($expectedResponseBody, (string) $response->getBody());
    }

    public static function levelDataProvider(): Generator
    {
        yield 'DEBUG' => [
            'level' => 'debug',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'INFO' => [
            'level' => 'info',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'NOTICE' => [
            'level' => 'notice',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'WARNING' => [
            'level' => 'warning',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'ERROR' => [
            'level' => 'error',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'CRITICAL' => [
            'level' => 'critical',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'ALERT' => [
            'level' => 'alert',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'EMERGENCY' => [
            'level' => 'emergency',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'INVALID-LEVEL' => [
            'level' => 'invalid-level',
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing level'
        ];
    }

    #[DataProvider('levelDataProvider')]
    public function testInvokeWithLevelLength(string $level, int $expectedStatusCode, string $expectedResponseBody): void
    {
        $invalidData = [...self::VALID_DATA, ...['level' => $level]];
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertStringContainsString($expectedResponseBody, (string) $response->getBody());
    }

    public static function datetimeDataProvider(): Generator
    {
        yield 'Valid RFC3339 Extended with microseconds' => [
            'datetime' => '2025-05-04T12:00:00.000+00:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Valid RFC3339 Extended with milliseconds' => [
            'datetime' => '2025-05-04T12:00:00.123+00:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Invalid RFC3339 Extended with full microseconds' => [
            'datetime' => '2025-05-04T12:00:00.123456+00:00',
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing datetime'
        ];
        yield 'Invalid RFC3339 Extended with UTC Z notation' => [
            'datetime' => '2025-05-04T12:00:00.000Z',
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing datetime'
        ];
        yield 'Valid RFC3339 Extended with different timezone' => [
            'datetime' => '2025-05-04T12:00:00.000-03:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Valid RFC3339 Extended with positive timezone' => [
            'datetime' => '2025-05-04T12:00:00.000+05:30',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Valid ISO8601 basic format UTC' => [
            'datetime' => '2025-05-04T12:00:00+00:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Valid ISO8601 basic format with negative timezone' => [
            'datetime' => '2025-05-04T12:00:00-03:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Valid ISO8601 basic format with positive timezone' => [
            'datetime' => '2025-05-04T12:00:00+05:30',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Invalid ISO8601 with UTC Z notation' => [
            'datetime' => '2025-05-04T12:00:00Z',
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing datetime'
        ];
        yield 'Invalid datetime format with space separator' => [
            'datetime' => '2025-05-04 12:00:00',
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing datetime'
        ];
        yield 'Invalid datetime string' => [
            'datetime' => 'invalid-date',
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing datetime'
        ];
        yield 'Missing datetime' => [
            'datetime' => null,
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing datetime'
        ];
    }

    #[DataProvider('datetimeDataProvider')]
    public function testInvokeWithDatetimeValidation(?string $datetime, int $expectedStatusCode, string $expectedResponseBody): void
    {
        $testData = [...self::VALID_DATA];
        if ($datetime === null) {
            unset($testData['datetime']);
        } else {
            $testData['datetime'] = $datetime;
        }

        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($testData));
        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertStringContainsString($expectedResponseBody, (string) $response->getBody());
    }

    public static function messageDataProvider(): Generator
    {
        yield 'Before min length' => [
            'message' => str_repeat('a', 2),
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing message'
        ];
        yield 'Min length' => [
            'message' => str_repeat('a', 3),
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'Max length' => [
            'message' => str_repeat('a', 255),
            'expectedStatusCode' => 201,
            'expectedResponseBody' => 'Received log'
        ];
        yield 'After max length' => [
            'message' => str_repeat('a', 256),
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing message'
        ];
    }

    #[DataProvider('messageDataProvider')]
    public function testInvokeWithMessageLength(string $message, int $expectedStatusCode, string $expectedResponseBody): void
    {
        $invalidData = [...self::VALID_DATA, ...['message' => $message]];
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertStringContainsString($expectedResponseBody, (string) $response->getBody());
    }

    private function executeAction(LogService $logService, ServerRequestInterface $request): ResponseInterface
    {
        return new ApiLogsAction($logService)->__invoke($request);
    }

    /** @throws Exception */
    private function createRequestMock(string $contentType = 'application/json'): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn($contentType);

        return $request;
    }
}
