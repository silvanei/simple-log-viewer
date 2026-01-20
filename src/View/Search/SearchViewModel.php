<?php

declare(strict_types=1);

namespace S3\Log\Viewer\View\Search;

use S3\Log\Viewer\View\TemplateEngine;

use function is_array;
use function is_string;

final readonly class SearchViewModel
{
    use TemplateEngine;

    public function highlightClass(mixed $value): string
    {
        if (is_string($value)) {
            $value = $this->cleanFts5Markers($value);
        }

        return match (true) {
            is_numeric($value) => 'highlight-number',
            $value === 'null' => 'highlight-null',
            $value === 'true' => 'highlight-boolean',
            $value === 'false' => 'highlight-boolean',
            is_string($value) => 'highlight-string',
            default => '',
        };
    }

    public function cleanFts5Markers(string $value): string
    {
        return str_replace(['⟦', '⟧'], '', $value);
    }

    protected function renderAdidionalKey(string $aditionalKey): string
    {
        return $this->escape($this->cleanFts5Markers($aditionalKey));
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
