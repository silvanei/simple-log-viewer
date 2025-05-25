<?php

declare(strict_types=1);

namespace S3\Log\Viewer\View\Search;

use S3\Log\Viewer\View\TemplateEngine;

final readonly class SearchViewModel
{
    use TemplateEngine;

    public function highlightClass(mixed $value): string
    {
        return match (true) {
            is_string($value) => "highlight-string",
            is_numeric($value) => "highlight-number",
            is_null($value) => "highlight-null",
            is_bool($value) => "highlight-boolean",
            default => '',
        };
    }

    public function renderValue(mixed $value): string
    {
        return match (true) {
            is_string($value) => sprintf('"%s"', str_replace("\n", "\n  ", $value)),
            is_numeric($value) => sprintf('%s', $value),
            is_null($value) => 'null',
            $value === true => 'true',
            $value === false => 'false',
            default => '',
        };
    }
}
