<?php

declare(strict_types=1);

namespace S3\Log\Viewer\Dto;

use Exception;

final class InvalidLogEntryDataException extends Exception
{
    /** @param array<string, string> $errors */
    public function __construct(string $message, public readonly array $errors)
    {
        parent::__construct($message);
    }
}
