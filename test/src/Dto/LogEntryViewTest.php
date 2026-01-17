<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Dto;

use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\Dto\LogEntryView;

final class LogEntryViewTest extends TestCase
{
    public function testConstructor_ShouldInitializeAllProperties(): void
    {
        $datetime = '2025-01-16T12:00:00+00:00';
        $channel = 'test-channel';
        $level = 'INFO';
        $message = 'Test message';
        $context = ['key' => 'value'];
        $extra = ['extra' => 'data'];

        $logEntryView = new LogEntryView(
            datetime: $datetime,
            channel: $channel,
            level: $level,
            message: $message,
            context: $context,
            extra: $extra
        );

        $this->assertSame($datetime, $logEntryView->datetime);
        $this->assertSame($channel, $logEntryView->channel);
        $this->assertSame($level, $logEntryView->level);
        $this->assertSame($message, $logEntryView->message);
        $this->assertSame($context, $logEntryView->context);
        $this->assertSame($extra, $logEntryView->extra);
    }

    public function testExtraProperty_ShouldDefaultToEmptyArray(): void
    {
        $datetime = '2025-01-16T12:00:00+00:00';
        $channel = 'test-channel';
        $level = 'INFO';
        $message = 'Test message';
        $context = ['key' => 'value'];

        $logEntryView = new LogEntryView(
            datetime: $datetime,
            channel: $channel,
            level: $level,
            message: $message,
            context: $context
        );

        $this->assertSame($datetime, $logEntryView->datetime);
        $this->assertSame($channel, $logEntryView->channel);
        $this->assertSame($level, $logEntryView->level);
        $this->assertSame($message, $logEntryView->message);
        $this->assertSame($context, $logEntryView->context);
        $this->assertSame([], $logEntryView->extra);
    }

    public function testExtraProperty_ShouldAcceptProvidedArray(): void
    {
        $datetime = '2025-01-16T12:00:00+00:00';
        $channel = 'test-channel';
        $level = 'INFO';
        $message = 'Test message';
        $context = ['key' => 'value'];
        $extra = ['custom' => 'extra', 'data' => 123];

        $logEntryView = new LogEntryView(
            datetime: $datetime,
            channel: $channel,
            level: $level,
            message: $message,
            context: $context,
            extra: $extra
        );

        $this->assertSame($extra, $logEntryView->extra);
    }

    public function testDatetimeProperty_ShouldAcceptString(): void
    {
        $datetime = '2025-01-16T12:00:00+00:00';

        $logEntryView = new LogEntryView(
            datetime: $datetime,
            channel: 'app',
            level: 'INFO',
            message: 'test',
            context: []
        );

        $this->assertSame($datetime, $logEntryView->datetime);
        $this->assertIsString($logEntryView->datetime);
    }

    public function testChannelProperty_ShouldAcceptString(): void
    {
        $channel = 'app';

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: $channel,
            level: 'INFO',
            message: 'test',
            context: []
        );

        $this->assertSame($channel, $logEntryView->channel);
        $this->assertIsString($logEntryView->channel);
    }

    public function testLevelProperty_ShouldAcceptString(): void
    {
        $level = 'ERROR';

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: $level,
            message: 'test',
            context: []
        );

        $this->assertSame($level, $logEntryView->level);
        $this->assertIsString($logEntryView->level);
    }

    public function testMessageProperty_ShouldAcceptString(): void
    {
        $message = 'User login successful';

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'INFO',
            message: $message,
            context: []
        );

        $this->assertSame($message, $logEntryView->message);
        $this->assertIsString($logEntryView->message);
    }

    public function testContextProperty_ShouldAcceptArrayData(): void
    {
        $context = ['user_id' => '123', 'ip' => '192.168.1.1'];

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'INFO',
            message: 'test',
            context: $context
        );

        $this->assertSame($context, $logEntryView->context);
        $this->assertIsArray($logEntryView->context);
    }

    public function testReadonlyProperties_ShouldNotBeModifiableAfterCreation(): void
    {
        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'test',
            level: 'INFO',
            message: 'test',
            context: []
        );

        $this->assertInstanceOf(LogEntryView::class, $logEntryView);

        $reflection = new \ReflectionClass($logEntryView);

        foreach (['datetime', 'channel', 'level', 'message', 'context', 'extra'] as $property) {
            $prop = $reflection->getProperty($property);
            $this->assertTrue($prop->isReadonly(), "Property {$property} should be readonly");
        }
    }

    public function testProperTypeDeclarations_ShouldHaveCorrectTypes(): void
    {
        $reflection = new \ReflectionClass(LogEntryView::class);

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

    public function testEdgeCaseEmptyContext_ShouldCreateLogEntryView(): void
    {
        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'DEBUG',
            message: 'Debug message',
            context: []
        );

        $this->assertSame([], $logEntryView->context);
        $this->assertEmpty($logEntryView->context);
    }

    public function testEdgeCaseEmptyMessage_ShouldCreateLogEntryView(): void
    {
        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'DEBUG',
            message: '',
            context: []
        );

        $this->assertSame('', $logEntryView->message);
        $this->assertEmpty($logEntryView->message);
    }

    public function testContextWithMixedKeys_ShouldAcceptStringAndIntKeys(): void
    {
        $context = [
            'string_key' => 'value',
            42 => 'int_key_value',
            'nested' => ['deep' => 'value']
        ];

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'test',
            level: 'INFO',
            message: 'test',
            context: $context
        );

        $this->assertSame($context, $logEntryView->context);
    }

    public function testExtraWithMixedKeys_ShouldAcceptStringAndIntKeys(): void
    {
        $extra = [
            'extra_string' => 'data',
            99 => 'extra_int_value'
        ];

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'test',
            level: 'INFO',
            message: 'test',
            context: [],
            extra: $extra
        );

        $this->assertSame($extra, $logEntryView->extra);
    }

    public function testContextWithNestedArrays_ShouldHandleNestedData(): void
    {
        $context = [
            'user' => [
                'id' => '123',
                'profile' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ]
            ],
            'request' => [
                'method' => 'POST',
                'uri' => '/api/users',
                'headers' => ['Authorization' => 'Bearer token']
            ]
        ];

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'INFO',
            message: 'Request received',
            context: $context
        );

        $this->assertSame($context, $logEntryView->context);
        $this->assertCount(2, $logEntryView->context);
    }

    public function testContextWithVariousTypes_ShouldHandleMixedValues(): void
    {
        $context = [
            'string' => 'value',
            'integer' => 42,
            'float' => 3.14,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3]
        ];

        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:00:00+00:00',
            channel: 'app',
            level: 'DEBUG',
            message: 'Mixed types test',
            context: $context
        );

        $this->assertSame($context, $logEntryView->context);
    }

    public function testRealisticLogEntry_ShouldHandleTypicalApplicationLog(): void
    {
        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T12:34:56.789+00:00',
            channel: 'app',
            level: 'INFO',
            message: 'User successfully logged in',
            context: [
                'user_id' => 'abc123',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'duration_ms' => 245
            ],
            extra: [
                'request_id' => 'req_987654321',
                'environment' => 'production',
                'version' => '2.1.0'
            ]
        );

        $this->assertSame('2025-01-16T12:34:56.789+00:00', $logEntryView->datetime);
        $this->assertSame('app', $logEntryView->channel);
        $this->assertSame('INFO', $logEntryView->level);
        $this->assertSame('User successfully logged in', $logEntryView->message);
        $this->assertCount(4, $logEntryView->context);
        $this->assertCount(3, $logEntryView->extra);
        $this->assertSame('abc123', $logEntryView->context['user_id']);
        $this->assertSame('req_987654321', $logEntryView->extra['request_id']);
    }

    public function testErrorLogEntry_ShouldHandleErrorSeverity(): void
    {
        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T15:30:45+00:00',
            channel: 'payment',
            level: 'ERROR',
            message: 'Payment processing failed',
            context: [
                'error_code' => 'CARD_DECLINED',
                'transaction_id' => 'txn_555444333',
                'amount' => 99.99,
                'currency' => 'USD'
            ],
            extra: [
                'trace_id' => 'trace_abc123',
                'service' => 'payment-gateway'
            ]
        );

        $this->assertSame('ERROR', $logEntryView->level);
        $this->assertStringContainsString('failed', $logEntryView->message);
        $this->assertSame('CARD_DECLINED', $logEntryView->context['error_code']);
        $this->assertSame(99.99, $logEntryView->context['amount']);
    }

    public function testWarningLogEntry_ShouldHandleWarningSeverity(): void
    {
        $logEntryView = new LogEntryView(
            datetime: '2025-01-16T09:15:20+00:00',
            channel: 'cache',
            level: 'WARNING',
            message: 'Cache miss rate exceeded threshold',
            context: [
                'cache_name' => 'user_sessions',
                'miss_rate' => 0.85,
                'threshold' => 0.70,
                'total_requests' => 10000
            ],
            extra: [
                'region' => 'us-west-2',
                'cluster' => 'prod-cluster-1'
            ]
        );

        $this->assertSame('WARNING', $logEntryView->level);
        $this->assertSame(0.85, $logEntryView->context['miss_rate']);
        $this->assertGreaterThan($logEntryView->context['threshold'], $logEntryView->context['miss_rate']);
    }
}
