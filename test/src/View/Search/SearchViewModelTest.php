<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\View\Search;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\View\Search\SearchViewModel;

class SearchViewModelTest extends TestCase
{
    private string $testTemplatesDir;

    #[Before]
    protected function setupTemplateDirectory(): void
    {
        $this->testTemplatesDir = getenv('APP_ROOT') . '/storage/templates_test_' . uniqid() . '/';
        mkdir($this->testTemplatesDir, recursive: true);
        putenv('TEMPLATES_ROOT=' . $this->testTemplatesDir);
    }

    #[After]
    protected function cleanupTemplateDirectory(): void
    {
        array_map('unlink', glob($this->testTemplatesDir . '*') ?: []);
        rmdir($this->testTemplatesDir);
        putenv('TEMPLATES_ROOT=');
    }

    public function testHighlightClass_ShouldReturnExpectedClass(): void
    {
        $viewModel = new SearchViewModel(template: 'dummy');

        $this->assertSame('highlight-string', $viewModel->highlightClass(value: 'text'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '123'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '45.67'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '123'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '45.67'));
        $this->assertSame('highlight-null', $viewModel->highlightClass(value: 'null'));
        $this->assertSame('highlight-boolean', $viewModel->highlightClass(value: 'true'));
        $this->assertSame('highlight-boolean', $viewModel->highlightClass(value: 'false'));
        $this->assertSame('', $viewModel->highlightClass(value: []));
    }

    public function testHighlightClass_ShouldRemoveHighlightMarkersFromStrings(): void
    {
        $viewModel = new SearchViewModel(template: 'dummy');

        $this->assertSame('highlight-string', $viewModel->highlightClass(value: '⟦text'));
        $this->assertSame('highlight-string', $viewModel->highlightClass(value: 'text⟧'));
        $this->assertSame('highlight-string', $viewModel->highlightClass(value: '⟦text⟧'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '⟦123⟧'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '⟦45.67⟧'));
    }

    public function testHighlightClass_ShouldHandleNonStringValues(): void
    {
        $viewModel = new SearchViewModel(template: 'dummy');

        $this->assertSame('highlight-string', $viewModel->highlightClass(value: '⟦text⟧'));
        $this->assertSame('highlight-string', $viewModel->highlightClass(value: 'plain'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '⟦123⟧'));
    }

    public function testRenderAdidionalKey_ShouldEscapeHTMLAndRemoveMarkers(): void
    {
        file_put_contents($this->testTemplatesDir . 'test.phtml', '<?= $this->renderAdidionalKey($this->key) ?>');

        $viewModel = new SearchViewModel(template: 'test', params: ['key' => '<script>']);
        $result = $viewModel->render();
        $this->assertSame('&lt;script&gt;', $result);

        $viewModel2 = new SearchViewModel(template: 'test', params: ['key' => 'test&co']);
        $result2 = $viewModel2->render();
        $this->assertSame('test&amp;co', $result2);

        $viewModel3 = new SearchViewModel(template: 'test', params: ['key' => '⟦⟧']);
        $result3 = $viewModel3->render();
        $this->assertSame('', $result3);
    }
}
