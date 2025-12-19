<?php

declare(strict_types=1);

namespace S3\Log\Viewer\View;

use RuntimeException;

trait TemplateEngine
{
    /** @param array<string, mixed> $params */
    public function __construct(
        private readonly string $template,
        public readonly array $params = [],
        private readonly ?string $extends = null
    ) {
    }

    public function render(): string
    {
        $path = $this->resolvePath();

        ob_start();
        include $path;
        $content = ob_get_clean() ?: '';

        if ($this->extends) {
            return $this->partial($this->extends, [...$this->params, 'slot' => $content]);
        }

        return $content;
    }

    /** @param array<string, mixed> $params */
    private function partial(string $template, array $params = []): string
    {
        return new self($template, $params)->render();
    }

    private function escape(string $value): string
    {
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $value = str_replace(['⟦', '⟧'], ['<mark>', '</mark>'], $value);

        if (str_contains($value, '<mark>') && ! str_contains($value, '</mark>')) {
            $value = "$value</mark>";
        }

        if (str_contains($value, '</mark>') && ! str_contains($value, '<mark>')) {
            $value = "<mark>$value";
        }

        return $value;
    }

    private function resolvePath(): string
    {
        $path = getenv('TEMPLATES_ROOT') . $this->template . '.phtml';
        if (! file_exists($path)) {
            throw new RuntimeException("Template not found: $path");
        }
        return $path;
    }

    public function __get(string $name): mixed
    {
        return $this->params[$name] ?? null;
    }
}
