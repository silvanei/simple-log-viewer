<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use Generator;
use PHPUnit\Framework\Attributes\Before;
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
        'datetime' => '2025-05-04T12:00:00.000+00:00',
        'channel' => 'channel',
        'level' => 'info',
        'message' => 'test',
        'context' => []
    ];

    private ServerRequestInterface&MockObject $request;
    private LogService&MockObject $logService;

    /** @throws Exception */
    #[Before]
    protected function init(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->request
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->logService = $this->createMock(LogService::class);
    }

    public function testWithValidDataReturns201Response(): void
    {
        $this->request
            ->method('getBody')
            ->willReturn(json_encode(self::VALID_DATA));
        $this->logService->expects($this->once())
            ->method('add')
            ->with(self::VALID_DATA);

        $response = $this->executeAction();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Received log', (string) $response->getBody());
        $this->assertEmpty($response->getHeader('Content-Type'));
    }

    public function testInvokeWithInvalidJsonReturns400Response(): void
    {
        $this->request
            ->method('getBody')
            ->willReturn('invalid json');
        $this->logService
            ->expects($this->never())
            ->method('add');

        $response = $this->executeAction();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertEquals('Syntax error', (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    public function testInvokeWhenLogServiceThrowsExceptionReturns400(): void
    {
        $this->request
            ->method('getBody')
            ->willReturn(json_encode(self::VALID_DATA));
        $this->logService
            ->method('add')
            ->willThrowException(new \RuntimeException($expectedMessage = 'Service unavailable'));

        $response = $this->executeAction();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame($expectedMessage, (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    /** @throws Exception */
    public function testInvokeWithUnsupportedMediaTypeReturns415Response(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->request
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/plain');

        $response = $this->executeAction();

        $this->assertSame(415, $response->getStatusCode());
        $this->assertSame('Unsupported Media Type', (string) $response->getBody());
        $this->assertSame(['text/html'], $response->getHeader('Content-Type'));
    }

    public function testInvokeWithMissingRequiredFieldsReturns400Response(): void
    {
        $invalidData = [];
        $this->request
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $this->logService
            ->expects($this->never())
            ->method('add');

        $response = $this->executeAction();

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
        $this->request
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $this->logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction();

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
        $this->request
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $this->logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction();

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
        yield 'Invalid RFC3339 basic without microseconds' => [
            'datetime' => '2025-05-04T12:00:00+00:00',
            'expectedStatusCode' => 400,
            'expectedResponseBody' => 'Invalid or missing datetime'
        ];
        yield 'Invalid datetime format' => [
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

        $this->request
            ->method('getBody')
            ->willReturn(json_encode($testData));
        $this->logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction();

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
        $this->request
            ->method('getBody')
            ->willReturn(json_encode($invalidData));
        $this->logService
            ->expects($expectedStatusCode === 201 ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction();

        $this->assertSame($expectedStatusCode, $response->getStatusCode());
        $this->assertStringContainsString($expectedResponseBody, (string) $response->getBody());
    }

    private function executeAction(): ResponseInterface
    {
        $action = new ApiLogsAction($this->logService);
        return $action->__invoke($this->request);
    }
}
