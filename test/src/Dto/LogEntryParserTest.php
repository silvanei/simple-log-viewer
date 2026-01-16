<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Dto;

use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\Dto\InvalidLogEntryDataException;
use S3\Log\Viewer\Dto\LogEntry;
use S3\Log\Viewer\Dto\LogEntryParser;

final class LogEntryParserTest extends TestCase
{
    public function testParseJsonWithValidJsonAllFields_ShouldCreateLogEntry(): void
    {
        $json = json_encode([
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => 'test-channel',
            'level' => 'info',
            'message' => 'Test message',
            'context' => ['key' => 'value'],
            'extra' => ['extra' => 'data']
        ]);

        $this->assertIsString($json);
        $logEntry = LogEntryParser::parseJson($json);

        $this->assertInstanceOf(LogEntry::class, $logEntry);
        $this->assertSame('2025-01-16T12:00:00+00:00', $logEntry->datetime);
        $this->assertSame('test-channel', $logEntry->channel);
        $this->assertSame('INFO', $logEntry->level);
        $this->assertSame('Test message', $logEntry->message);
        $this->assertSame(['key' => 'value'], $logEntry->context);
        $this->assertSame(['extra' => 'data'], $logEntry->extra);
    }

    public function testParseJsonWithMissingExtraField_ShouldCreateLogEntryWithEmptyExtra(): void
    {
        $json = json_encode([
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => 'test-channel',
            'level' => 'info',
            'message' => 'Test message',
            'context' => ['key' => 'value']
        ]);

        $this->assertIsString($json);
        $logEntry = LogEntryParser::parseJson($json);

        $this->assertInstanceOf(LogEntry::class, $logEntry);
        $this->assertSame([], $logEntry->extra);
    }

    public function testParseJsonWithEmptyExtraField_ShouldCreateLogEntryWithEmptyExtra(): void
    {
        $json = json_encode([
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => 'test-channel',
            'level' => 'info',
            'message' => 'Test message',
            'context' => ['key' => 'value'],
            'extra' => []
        ]);

        $this->assertIsString($json);
        $logEntry = LogEntryParser::parseJson($json);

        $this->assertInstanceOf(LogEntry::class, $logEntry);
        $this->assertSame([], $logEntry->extra);
    }

    public function testParseJsonWithInvalidJson_ShouldThrowJsonException(): void
    {
        $this->expectException(JsonException::class);
        LogEntryParser::parseJson('invalid json string');
    }

    public function testParseArrayWithValidArrayData_ShouldCreateLogEntry(): void
    {
        $data = [
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => 'test-channel',
            'level' => 'error',
            'message' => 'Error message',
            'context' => ['error' => 'details'],
            'extra' => ['trace' => 'stack']
        ];

        $json = json_encode($data);
        $this->assertIsString($json);
        $logEntry = LogEntryParser::parseJson($json);

        $this->assertInstanceOf(LogEntry::class, $logEntry);
        $this->assertSame('2025-01-16T12:00:00+00:00', $logEntry->datetime);
        $this->assertSame('test-channel', $logEntry->channel);
        $this->assertSame('ERROR', $logEntry->level);
        $this->assertSame('Error message', $logEntry->message);
        $this->assertSame(['error' => 'details'], $logEntry->context);
        $this->assertSame(['trace' => 'stack'], $logEntry->extra);
    }

    /** @return Generator<array{data: array{datetime?: string, channel?: string, level?: string, message?: string, context?: array<string|int, mixed>}, expectedErrorField: string}> */
    public static function missingRequiredFieldsProvider(): Generator
    {
        yield 'missing datetime' => [
            'data' => [
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => []
            ],
            'expectedErrorField' => 'datetime'
        ];

        yield 'missing channel' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'level' => 'info',
                'message' => 'test',
                'context' => []
            ],
            'expectedErrorField' => 'channel'
        ];

        yield 'missing level' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'message' => 'test',
                'context' => []
            ],
            'expectedErrorField' => 'level'
        ];

        yield 'missing message' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'context' => []
            ],
            'expectedErrorField' => 'message'
        ];

        yield 'missing context' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test'
            ],
            'expectedErrorField' => 'context'
        ];
    }

    /** @param array<int,mixed> $data */
    #[DataProvider('missingRequiredFieldsProvider')]
    public function testParseArrayWithMissingRequiredFields_ShouldThrowException(array $data, string $expectedErrorField): void
    {
        $this->expectException(InvalidLogEntryDataException::class);
        $this->expectExceptionMessage('Invalid log entry data');

        try {
            $json = json_encode($data);
            $this->assertIsString($json);
            LogEntryParser::parseJson($json);
        } catch (InvalidLogEntryDataException $e) {
            $this->assertArrayHasKey($expectedErrorField, $e->errors);
            throw $e;
        }
    }

    /** @return Generator<string,array{string}> */
    public static function invalidDatetimeProvider(): Generator
    {
        yield 'invalid format' => ['invalid-date'];
        yield 'missing timezone' => ['2025-01-16T12:00:00'];
        yield 'space instead of T' => ['2025-01-16 12:00:00+00:00'];
        yield 'UTC Z notation' => ['2025-01-16T12:00:00Z'];
        yield 'invalid timezone' => ['2025-01-16T12:00:00+99:99'];
    }

    #[DataProvider('invalidDatetimeProvider')]
    public function testParseArrayWithInvalidDatetimeFormats_ShouldThrowException(string $datetime): void
    {
        $data = [
            'datetime' => $datetime,
            'channel' => 'test',
            'level' => 'info',
            'message' => 'test',
            'context' => []
        ];

        $this->expectException(InvalidLogEntryDataException::class);

        try {
            $json = json_encode($data);
            $this->assertIsString($json);
            LogEntryParser::parseJson($json);
        } catch (InvalidLogEntryDataException $e) {
            $this->assertArrayHasKey('datetime', $e->errors);
            throw $e;
        }
    }

    /** @return Generator<string,array{string}|array{int}|array{null}|array{array<int,string>}|array{object}> */
    public static function invalidLevelProvider(): Generator
    {
        yield 'invalid level string' => ['INVALID'];
        yield 'numeric level' => [123];
        yield 'null level' => [null];
        yield 'empty level' => [''];
        yield 'array level' => [['level']];
        yield 'object level' => [(object)['level' => 'value']];
    }

    #[DataProvider('invalidLevelProvider')]
    public function testParseArrayWithInvalidLevelValues_ShouldThrowException(mixed $level): void
    {
        $data = [
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => 'test',
            'level' => $level,
            'message' => 'test',
            'context' => []
        ];

        $this->expectException(InvalidLogEntryDataException::class);

        try {
            $json = json_encode($data);
            $this->assertIsString($json);
            LogEntryParser::parseJson($json);
        } catch (InvalidLogEntryDataException $e) {
            $this->assertArrayHasKey('level', $e->errors);
            throw $e;
        }
    }

    /** @return Generator<array{data: array{datetime: string, channel: string, level: string, message: mixed, context: mixed}, expectedError: string}> */
    public static function invalidFieldTypesProvider(): Generator
    {
        yield 'non-string message' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 123,
                'context' => []
            ],
            'expectedError' => 'message'
        ];

        yield 'non-array context' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => 'not-array'
            ],
            'expectedError' => 'context'
        ];

        yield 'null context' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => null
            ],
            'expectedError' => 'context'
        ];

        yield 'object context' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => 'not-an-array'
            ],
            'expectedError' => 'context'
        ];
    }

    /** @param array<mixed> $data */
    #[DataProvider('invalidFieldTypesProvider')]
    public function testParseArrayWithInvalidFieldTypes_ShouldThrowException(array $data, string $expectedError): void
    {
        $this->expectException(InvalidLogEntryDataException::class);

        try {
            $json = json_encode($data);
            $this->assertIsString($json);
            LogEntryParser::parseJson($json);
        } catch (InvalidLogEntryDataException $e) {
            $this->assertArrayHasKey($expectedError, $e->errors);
            throw $e;
        }
    }

    /** @return Generator<array{data: array{datetime: string, channel: string, level: string, message: string, context: array<string|int, mixed>, extra: mixed}}> */
    public static function invalidExtraFieldProvider(): Generator
    {
        yield 'string extra' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => [],
                'extra' => 'string-value'
            ]
        ];
        yield 'numeric extra' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => [],
                'extra' => 123
            ]
        ];
        yield 'null extra' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => [],
                'extra' => 'null-as-string'
            ]
        ];
        yield 'boolean extra' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => [],
                'extra' => 'boolean-as-string'
            ]
        ];
        yield 'object extra' => [
            'data' => [
                'datetime' => '2025-01-16T12:00:00+00:00',
                'channel' => 'test',
                'level' => 'info',
                'message' => 'test',
                'context' => [],
                'extra' => 'object-as-string'
            ]
        ];
    }

    /** @param array<int,mixed> $data */
    #[DataProvider('invalidExtraFieldProvider')]
    public function testParseArrayWithInvalidExtraField_ShouldThrowException(array $data): void
    {
        $this->expectException(InvalidLogEntryDataException::class);

        try {
            $json = json_encode($data);
            $this->assertIsString($json);
            LogEntryParser::parseJson($json);
        } catch (InvalidLogEntryDataException $e) {
            $this->assertArrayHasKey('extra', $e->errors);
            throw $e;
        }
    }

    /** @return Generator<string,array{string,bool}> */
    public static function boundaryChannelProvider(): Generator
    {
        yield 'min length' => ['abc', true];
        yield 'max length' => [str_repeat('a', 255), true];
        yield 'too short' => [str_repeat('a', 2), false];
        yield 'too long' => [str_repeat('a', 256), false];
    }

    #[DataProvider('boundaryChannelProvider')]
    public function testBoundaryTestingChannelLength(string $channel, bool $shouldBeValid): void
    {
        $data = [
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => $channel,
            'level' => 'info',
            'message' => 'test',
            'context' => []
        ];

        if ($shouldBeValid) {
            $json = json_encode($data);
            $this->assertIsString($json);
            $logEntry = LogEntryParser::parseJson($json);
            $this->assertSame($channel, $logEntry->channel);
        } else {
            $this->expectException(InvalidLogEntryDataException::class);
            try {
                $json = json_encode($data);
                $this->assertIsString($json);
                LogEntryParser::parseJson($json);
            } catch (InvalidLogEntryDataException $e) {
                $this->assertArrayHasKey('channel', $e->errors);
                throw $e;
            }
        }
    }

    /** @return Generator<string,array{string,bool}> */
    public static function boundaryMessageProvider(): Generator
    {
        yield 'min length' => ['abc', true];
        yield 'max length' => [str_repeat('a', 255), true];
        yield 'too short' => [str_repeat('a', 2), false];
        yield 'too long' => [str_repeat('a', 256), false];
    }

    #[DataProvider('boundaryMessageProvider')]
    public function testBoundaryTestingMessageLength(string $message, bool $shouldBeValid): void
    {
        $data = [
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => 'test',
            'level' => 'info',
            'message' => $message,
            'context' => []
        ];

        if ($shouldBeValid) {
            $json = json_encode($data);
            $this->assertIsString($json);
            $logEntry = LogEntryParser::parseJson($json);
            $this->assertSame($message, $logEntry->message);
        } else {
            $this->expectException(InvalidLogEntryDataException::class);
            try {
                $json = json_encode($data);
                $this->assertIsString($json);
                LogEntryParser::parseJson($json);
            } catch (InvalidLogEntryDataException $e) {
                $this->assertArrayHasKey('message', $e->errors);
                throw $e;
            }
        }
    }

    /** @return Generator<string,array{string,string}> */
    public static function psr3LogLevelsProvider(): Generator
    {
        yield 'debug' => ['debug', 'DEBUG'];
        yield 'info' => ['info', 'INFO'];
        yield 'notice' => ['notice', 'NOTICE'];
        yield 'warning' => ['warning', 'WARNING'];
        yield 'error' => ['error', 'ERROR'];
        yield 'critical' => ['critical', 'CRITICAL'];
        yield 'alert' => ['alert', 'ALERT'];
        yield 'emergency' => ['emergency', 'EMERGENCY'];
        yield 'uppercase DEBUG' => ['DEBUG', 'DEBUG'];
        yield 'mixed case Info' => ['Info', 'INFO'];
    }

    #[DataProvider('psr3LogLevelsProvider')]
    public function testPsr3LogLevelValidation(string $inputLevel, string $expectedLevel): void
    {
        $data = [
            'datetime' => '2025-01-16T12:00:00+00:00',
            'channel' => 'test',
            'level' => $inputLevel,
            'message' => 'test',
            'context' => []
        ];

        $json = json_encode($data);
        $this->assertIsString($json);
        $logEntry = LogEntryParser::parseJson($json);
        $this->assertSame($expectedLevel, $logEntry->level);
    }
}
