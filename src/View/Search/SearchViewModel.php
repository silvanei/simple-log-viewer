<?php

declare(strict_types=1);

namespace S3\Log\Viewer\View\Search;

use S3\Log\Viewer\View\TemplateEngine;

use function is_array;
use function is_string;
use function is_null;
use function is_bool;

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
            is_string($value) => sprintf('%s', $value),
            is_numeric($value) => sprintf('%s', $value),
            is_null($value) => 'null',
            $value === true => 'true',
            $value === false => 'false',
            default => '',
        };
    }
    /**
     * @param mixed[] $input
     * @return mixed[]
     */
    public function flattenWithDots(array $input, string $prefix = ''): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            $newKey = $prefix === '' ? $key : "$prefix.$key";
            if (is_array($value)) {
                $result += $this->flattenWithDots($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
