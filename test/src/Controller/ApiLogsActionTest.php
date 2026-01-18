<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use S3\Log\Viewer\Controller\ApiLogsAction;
use S3\Log\Viewer\Dto\LogEntry;
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

    private const string EXPECTED_SUCCESS_JSON = <<<'JSON'
    {
        "message": "Log entry received"
    }

    JSON;

    private const string EXPECTED_UNSUPPORTED_MEDIA_TYPE_JSON =  <<<'JSON'
    {
        "error": "Unsupported Media Type. Expected application/json"
    }

    JSON;

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
            ->with($this->isInstanceOf(LogEntry::class));

        $response = $this->executeAction($logService, $request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_SUCCESS_JSON, (string) $response->getBody());
        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
    }

    public function testInvokeWithInvalidJsonThrowsJsonException(): void
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

        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $this->executeAction($logService, $request);
    }

    public function testInvokeWhenLogServiceThrowsExceptionThrowsRuntimeException(): void
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->executeAction($logService, $request);
    }

    /** @throws Exception */
    public function testInvokeWithUnsupportedMediaTypeReturns415Response(): void
    {
        $logService = $this->createStub(LogService::class);
        $request = $this->createRequestMock(contentType: 'text/plain');

        $response = $this->executeAction($logService, $request);

        $this->assertSame(415, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_UNSUPPORTED_MEDIA_TYPE_JSON, (string) $response->getBody());
        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
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
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'Max length' => [
            'channel' => str_repeat('a', 255),
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
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
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'INFO' => [
            'level' => 'info',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'NOTICE' => [
            'level' => 'notice',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'WARNING' => [
            'level' => 'warning',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'ERROR' => [
            'level' => 'error',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'CRITICAL' => [
            'level' => 'critical',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'ALERT' => [
            'level' => 'alert',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'EMERGENCY' => [
            'level' => 'emergency',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
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
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'Valid RFC3339 Extended with milliseconds' => [
            'datetime' => '2025-05-04T12:00:00.123+00:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
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
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'Valid RFC3339 Extended with positive timezone' => [
            'datetime' => '2025-05-04T12:00:00.000+05:30',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'Valid ISO8601 basic format UTC' => [
            'datetime' => '2025-05-04T12:00:00+00:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'Valid ISO8601 basic format with negative timezone' => [
            'datetime' => '2025-05-04T12:00:00-03:00',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'Valid ISO8601 basic format with positive timezone' => [
            'datetime' => '2025-05-04T12:00:00+05:30',
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
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
            'expectedResponseBody' => '"message": "Log entry received"'
        ];
        yield 'Max length' => [
            'message' => str_repeat('a', 255),
            'expectedStatusCode' => 201,
            'expectedResponseBody' => '"message": "Log entry received"'
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
    public function testInvokeWithValidExtraArray_ShouldReturn201Status(): void
    {
        $data = [
            ...self::VALID_DATA,
            ...['extra' => ['custom_field' => 'custom_value', 'numeric' => 123]]
        ];

        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($data));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(LogEntry::class));

        $response = $this->executeAction($logService, $request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_SUCCESS_JSON, (string) $response->getBody());
    }

    public function testInvokeWithInvalidExtraNonArray_ShouldReturn400Status(): void
    {
        $data = [
            ...self::VALID_DATA,
            ...['extra' => 'invalid_extra_field']
        ];

        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($data));

        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Invalid extra field', (string) $response->getBody());
    }

    public function testInvokeWithMissingExtraField_ShouldReturn201StatusWithDefaultEmptyExtra(): void
    {
        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(self::VALID_DATA));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('add')
            ->with($this->callback(function (LogEntry $logEntry) {
                return $logEntry->extra === [];
            }));

        $response = $this->executeAction($logService, $request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_SUCCESS_JSON, (string) $response->getBody());
    }

    public function testInvokeWithEmptyExtraArray_ShouldReturn201Status(): void
    {
        $data = [
            ...self::VALID_DATA,
            ...['extra' => []]
        ];

        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($data));

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())
            ->method('add')
            ->with($this->callback(function (LogEntry $logEntry) {
                return $logEntry->extra === [];
            }));

        $response = $this->executeAction($logService, $request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_SUCCESS_JSON, (string) $response->getBody());
    }

    public static function extraFieldValidationProvider(): Generator
    {
        yield 'null extra (treated as missing)' => ['extra' => null, 'shouldPass' => true];
        yield 'string extra' => ['extra' => 'string', 'shouldPass' => false];
        yield 'numeric extra' => ['extra' => 123, 'shouldPass' => false];
        yield 'boolean extra true' => ['extra' => true, 'shouldPass' => false];
        yield 'boolean extra false' => ['extra' => false, 'shouldPass' => false];
        yield 'object extra (becomes array)' => ['extra' => (object)['key' => 'value'], 'shouldPass' => true];
        yield 'empty array extra' => ['extra' => [], 'shouldPass' => true];
        yield 'associative array extra' => ['extra' => ['key' => 'value'], 'shouldPass' => true];
        yield 'indexed array extra' => ['extra' => ['item1', 'item2'], 'shouldPass' => true];
        yield 'nested array extra' => ['extra' => ['nested' => ['deep' => 'value']], 'shouldPass' => true];
        yield 'mixed keys array extra' => ['extra' => ['string' => 'value', 42 => 'int_key'], 'shouldPass' => true];
    }

    #[DataProvider('extraFieldValidationProvider')]
    public function testInvokeWithExtraFieldValidation(mixed $extra, bool $shouldPass): void
    {
        $data = [
            ...self::VALID_DATA,
            ...['extra' => $extra]
        ];

        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode($data));

        $logService = $this->createMock(LogService::class);
        $logService
            ->expects($shouldPass ? $this->once() : $this->never())
            ->method('add');

        $response = $this->executeAction($logService, $request);

        if ($shouldPass) {
            $this->assertSame(201, $response->getStatusCode());
            $this->assertStringContainsString('"message": "Log entry received"', (string) $response->getBody());
        } else {
            $this->assertSame(400, $response->getStatusCode());
            $responseBody = (string) $response->getBody();
            if (str_contains($responseBody, 'Invalid extra field')) {
                $this->assertStringContainsString('Invalid extra field', $responseBody);
            } else {
                $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
                $responseData = json_decode($responseBody, true);
                $this->assertIsArray($responseData);
                $this->assertArrayHasKey('errors', $responseData);
                $errors = $responseData['errors'];
                $this->assertIsArray($errors);
                $this->assertArrayHasKey('extra', $errors);
                $this->assertSame('Invalid extra field', $errors['extra']);
            }
        }
    }

    private function createRequestMock(string $contentType = 'application/json'): ServerRequestInterface & MockObject
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
