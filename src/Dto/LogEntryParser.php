<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Dto;

use DateTimeInterface;
use JsonException;
use Respect\Validation\Validator as v;
use S3\Log\Viewer\Dto\InvalidLogEntryDataException;
use S3\Log\Viewer\Dto\LogEntry;
use Throwable;

final class LogEntryParser
{
    /**
     * @throws JsonException
     * @throws Throwable
     */
    public static function parseJson(string $json): LogEntry
    {
        /** @var array{datetime?: string, channel?: string, level?: string, message?: string, context?: mixed, extra?: mixed} $data */
        $data = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        return self::parseArray($data);
    }

    /**
     * @param array<string|int, mixed> $data
     * @throws Throwable
     */
    private static function parseArray(array $data): LogEntry
    {
        $errors = [];

        $datetimeValid = v::dateTime('Y-m-d\TH:i:sP')->validate($data['datetime'] ?? null) ||
                       v::dateTime(DateTimeInterface::RFC3339_EXTENDED)->validate($data['datetime'] ?? null);

        if (! $datetimeValid) {
            $errors['datetime'] = 'Invalid or missing datetime';
        }

        if (! v::stringType()->length(3, 255)->validate($data['channel'] ?? null)) {
            $errors['channel'] = 'Invalid or missing channel';
        }

        $validLevels = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
        if (! isset($data['level']) || ! is_string($data['level'])) {
            $errors['level'] = 'Invalid or missing level';
        } elseif (! v::stringType()->in($validLevels)->validate(strtoupper($data['level']))) {
            $errors['level'] = 'Invalid or missing level';
        }
        $level = $data['level'] ?? '';

        if (! v::stringType()->length(3, 255)->validate($data['message'] ?? null)) {
            $errors['message'] = 'Invalid or missing message';
        }

        if (! v::arrayType()->validate($data['context'] ?? null)) {
            $errors['context'] = 'Invalid or missing context';
        }

        if (isset($data['extra']) && ! v::arrayType()->validate($data['extra'])) {
            $errors['extra'] = 'Invalid extra field';
        }

        if (! empty($errors)) {
            throw new InvalidLogEntryDataException('Invalid log entry data', $errors);
        }

        /** @var array{datetime: string, channel: string, level: string, message: string, context: array<string|int, mixed>, extra?: array<string|int, mixed>} $data */
        return new LogEntry(
            datetime: $data['datetime'],
            channel: $data['channel'],
            level: strtoupper((string) $data['level']),
            message: $data['message'],
            context: $data['context'],
            extra: $data['extra'] ?? [],
        );
    }
}
