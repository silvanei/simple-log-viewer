<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Dto;

use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\Dto\InvalidLogEntryDataException;

final class InvalidLogEntryDataExceptionTest extends TestCase
{
    public function testConstructorWithMessageAndErrorsArray_ShouldCreateException(): void
    {
        $message = 'Test error message';
        $errors = [
            'datetime' => 'Invalid datetime format',
            'channel' => 'Channel too short',
            'level' => 'Invalid log level'
        ];

        $exception = new InvalidLogEntryDataException($message, $errors);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($errors, $exception->errors);
    }

    public function testErrorsPropertyIsReadonlyAndAccessible_ShouldReturnErrors(): void
    {
        $errors = ['field' => 'error message'];
        $exception = new InvalidLogEntryDataException('Test message', $errors);

        $this->assertSame($errors, $exception->errors);

        $reflection = new \ReflectionClass($exception);
        $errorsProperty = $reflection->getProperty('errors');
        $this->assertTrue($errorsProperty->isReadonly());
    }

    public function testExceptionExtendsStandardException_ShouldBeInstanceOfException(): void
    {
        $exception = new InvalidLogEntryDataException('Test message', []);

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionMessageIsProperlyStored_ShouldReturnGivenMessage(): void
    {
        $message = 'Custom error message';
        $exception = new InvalidLogEntryDataException($message, []);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithEmptyErrorsArray_ShouldHaveEmptyErrors(): void
    {
        $exception = new InvalidLogEntryDataException('Test message', []);

        $this->assertSame([], $exception->errors);
        $this->assertEmpty($exception->errors);
    }

    public function testExceptionWithSingleError_ShouldHaveOneError(): void
    {
        $errors = ['field' => 'error description'];
        $exception = new InvalidLogEntryDataException('Test message', $errors);

        $this->assertCount(1, $exception->errors);
        $this->assertArrayHasKey('field', $exception->errors);
        $this->assertSame('error description', $exception->errors['field']);
    }

    public function testExceptionWithMultipleErrors_ShouldHaveAllErrors(): void
    {
        $errors = [
            'field1' => 'first error',
            'field2' => 'second error',
            'field3' => 'third error'
        ];
        $exception = new InvalidLogEntryDataException('Test message', $errors);

        $this->assertCount(3, $exception->errors);
        $this->assertSame('first error', $exception->errors['field1']);
        $this->assertSame('second error', $exception->errors['field2']);
        $this->assertSame('third error', $exception->errors['field3']);
    }

    public function testExceptionCodeDefault_ShouldHaveZeroCode(): void
    {
        $exception = new InvalidLogEntryDataException('Test message', []);

        $this->assertSame(0, $exception->getCode());
    }

    public function testExceptionPreviousDefault_ShouldHaveNoPrevious(): void
    {
        $exception = new InvalidLogEntryDataException('Test message', []);

        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionToString_ShouldIncludeMessageAndErrors(): void
    {
        $message = 'Validation failed';
        $errors = ['field' => 'error'];
        $exception = new InvalidLogEntryDataException($message, $errors);

        $stringRepresentation = (string) $exception;

        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString(InvalidLogEntryDataException::class, $stringRepresentation);
    }

    public function testErrorsPropertyTypeDeclaration_ShouldBeArray(): void
    {
        $reflection = new \ReflectionClass(InvalidLogEntryDataException::class);
        $errorsProperty = $reflection->getProperty('errors');

        $type = $errorsProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertFalse($type->allowsNull());
    }
}
