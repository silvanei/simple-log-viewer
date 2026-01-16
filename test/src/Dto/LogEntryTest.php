<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Dto;

use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\Dto\LogEntry;

final class LogEntryTest extends TestCase
{
    public function testConstructorWithAllRequiredParameters_ShouldCreateLogEntry(): void
    {
        $datetime = '2025-01-16T12:00:00+00:00';
        $channel = 'test-channel';
        $level = 'INFO';
        $message = 'Test message';
        $context = ['key' => 'value'];
        $extra = ['extra' => 'data'];

        $logEntry = new LogEntry(
            datetime: $datetime,
            channel: $channel,
            level: $level,
            message: $message,
            context: $context,
            extra: $extra
        );

        $this->assertSame($datetime, $logEntry->datetime);
        $this->assertSame($channel, $logEntry->channel);
        $this->assertSame($level, $logEntry->level);
        $this->assertSame($message, $logEntry->message);
        $this->assertSame($context, $logEntry->context);
        $this->assertSame($extra, $logEntry->extra);
    }

    public function testConstructorWithDefaultExtraField_ShouldCreateLogEntryWithEmptyExtra(): void
    {
        $datetime = '2025-01-16T12:00:00+00:00';
        $channel = 'test-channel';
        $level = 'INFO';
        $message = 'Test message';
        $context = ['key' => 'value'];

        $logEntry = new LogEntry(
            datetime: $datetime,
            channel: $channel,
            level: $level,
            message: $message,
            context: $context
        );

        $this->assertSame($datetime, $logEntry->datetime);
        $this->assertSame($channel, $logEntry->channel);
        $this->assertSame($level, $logEntry->level);
        $this->assertSame($message, $logEntry->message);
        $this->assertSame($context, $logEntry->context);
        $this->assertSame([], $logEntry->extra);
    }

    public function testConstructorWithProvidedExtraField_ShouldCreateLogEntryWithProvidedExtra(): void
    {
        $datetime = '2025-01-16T12:00:00+00:00';
        $channel = 'test-channel';
        $level = 'INFO';
        $message = 'Test message';
        $context = ['key' => 'value'];
        $extra = ['custom' => 'extra', 'data' => 123];

        $logEntry = new LogEntry(
            datetime: $datetime,
            channel: $channel,
            level: $level,
            message: $message,
            context: $context,
            extra: $extra
        );

        $this->assertSame($extra, $logEntry->extra);
    }

    public function testReadonlyProperties_ShouldNotBeModifiableAfterCreation(): void
    {
        $logEntry = new LogEntry(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'test',
            level: 'INFO',
            message: 'test',
            context: []
        );

        $this->assertInstanceOf(LogEntry::class, $logEntry);

        $reflection = new \ReflectionClass($logEntry);

        foreach (['datetime', 'channel', 'level', 'message', 'context', 'extra'] as $property) {
            $prop = $reflection->getProperty($property);
            $this->assertTrue($prop->isReadonly(), "Property {$property} should be readonly");
        }
    }

    public function testProperTypeDeclarations_ShouldHaveCorrectTypes(): void
    {
        $reflection = new \ReflectionClass(LogEntry::class);

        $datetimeProperty = $reflection->getProperty('datetime');
        $datetimeType = $datetimeProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $datetimeType);
        $this->assertSame('string', $datetimeType->getName());

        $channelProperty = $reflection->getProperty('channel');
        $channelType = $channelProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $channelType);
        $this->assertSame('string', $channelType->getName());

        $levelProperty = $reflection->getProperty('level');
        $levelType = $levelProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $levelType);
        $this->assertSame('string', $levelType->getName());

        $messageProperty = $reflection->getProperty('message');
        $messageType = $messageProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $messageType);
        $this->assertSame('string', $messageType->getName());

        $contextProperty = $reflection->getProperty('context');
        $contextType = $contextProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $contextType);
        $this->assertSame('array', $contextType->getName());

        $extraProperty = $reflection->getProperty('extra');
        $extraType = $extraProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $extraType);
        $this->assertSame('array', $extraType->getName());
    }

    public function testEdgeCaseEmptyStrings_ShouldCreateLogEntry(): void
    {
        $logEntry = new LogEntry(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'DEBUG',
            message: '',
            context: []
        );

        $this->assertSame('', $logEntry->message);
    }

    public function testEdgeCaseMinimalValidData_ShouldCreateLogEntry(): void
    {
        $logEntry = new LogEntry(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'INFO',
            message: 'test',
            context: []
        );

        $this->assertSame('2025-01-16T12:00:00+00:00', $logEntry->datetime);
        $this->assertSame('app', $logEntry->channel);
        $this->assertSame('INFO', $logEntry->level);
        $this->assertSame('test', $logEntry->message);
        $this->assertSame([], $logEntry->context);
        $this->assertSame([], $logEntry->extra);
    }

    public function testContextAndExtraWithMixedKeys_ShouldAcceptStringAndIntKeys(): void
    {
        $context = [
            'string_key' => 'value',
            42 => 'int_key_value',
            'nested' => ['deep' => 'value']
        ];

        $extra = [
            'extra_string' => 'data',
            99 => 'extra_int_value'
        ];

        $logEntry = new LogEntry(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'test',
            level: 'INFO',
            message: 'test',
            context: $context,
            extra: $extra
        );

        $this->assertSame($context, $logEntry->context);
        $this->assertSame($extra, $logEntry->extra);
    }
}
