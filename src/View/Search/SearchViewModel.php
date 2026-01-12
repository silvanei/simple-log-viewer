<?php

declare(strict_types=1);

namespace S3\Log\Viewer\View\Search;

use S3\Log\Viewer\View\TemplateEngine;

use function is_array;
use function is_string;
use function sprintf;
use function is_bool;

final readonly class SearchViewModel
{
    use TemplateEngine;

    public function highlightClass(mixed $value): string
    {
        if (is_string($value)) {
            $value = str_replace(['⟦', '⟧'], ['', ''], $value);
        }

        return match (true) {
            is_numeric($value) => "highlight-number",
            $value === null => "highlight-null",
            is_bool($value) => "highlight-boolean",
            is_string($value) => "highlight-string",
            default => '',
        };
    }

    public function renderValue(mixed $value): string
    {
        return match (true) {
            is_numeric($value) => sprintf('%s', $value),
            $value === null => 'null',
            $value === true => 'true',
            $value === false => 'false',
            is_string($value) => sprintf('%s', $value),
            default => '',
        };
    }

    protected function renderAdidionalKey(string $aditionalKey): string
    {
        return $aditionalKey
            |> (fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
            |> (fn (string $value): string => str_replace(['⟦', '⟧'], ['', ''], $value));
    }

    /** @param array<string, string> $flattenWithDotsentry */
    protected function renderAdidionalField(array $flattenWithDotsentry, string $field): string
    {
        if (array_key_exists($field, $flattenWithDotsentry)) {
            return $this->escape($flattenWithDotsentry[$field]);
        }

        $indexed = [];
        foreach ($flattenWithDotsentry as $key => $value) {
            $indexed[$this->renderAdidionalKey($key)] = $value;
        }

        return $this->escape($indexed[$field] ?? '-');
    }

    /**
     * @param mixed[] $input
     * @return mixed[]
     */
    protected function flattenWithDots(array $input, string $prefix = ''): array
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
