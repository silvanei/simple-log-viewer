<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\View;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\View\Search\SearchViewModel;

class TemplateEngineTest extends TestCase
{
    private static string $templatesRoot;
    private static string $testTemplatesDir;

    #[Before]
    public static function setupTemplateRootTemporaryDirectory(): void
    {
        self::$testTemplatesDir = getenv('APP_ROOT') . '/storage/templates_test_' . uniqid() . '/';
        mkdir(self::$testTemplatesDir, recursive: true);
        self::$templatesRoot = getenv('TEMPLATES_ROOT') ?: '';
        putenv('TEMPLATES_ROOT=' . self::$testTemplatesDir);
    }

    #[After]
    public static function clearTemplateRootTemporaryDirectory(): void
    {
        array_map('unlink', glob(self::$testTemplatesDir . '*') ?: []);
        rmdir(self::$testTemplatesDir);
        putenv('TEMPLATES_ROOT=' . self::$templatesRoot);
    }

    public function testEscape_ShouldConvertHighlightMarkersToHTML(): void
    {
        file_put_contents(self::$testTemplatesDir . 'test.phtml', '<?= $this->escape($this->value) ?>');

        $viewModel = new SearchViewModel(template: 'test', params: ['value' => '⟦word⟧']);
        $result = $viewModel->render();

        $this->assertStringContainsString('<mark>word</mark>', $result);
    }

    public function testEscape_ShouldConvertMultipleMarkerPairs(): void
    {
        file_put_contents(self::$testTemplatesDir . 'test.phtml', '<?= $this->escape($this->value) ?>');

        $viewModel = new SearchViewModel(template: 'test', params: ['value' => '⟦a⟧ and ⟦b⟧']);
        $result = $viewModel->render();

        $this->assertStringContainsString('<mark>a</mark> and <mark>b</mark>', $result);
    }

    public function testEscape_ShouldBalanceUnbalancedOpeningMarkers(): void
    {
        file_put_contents(self::$testTemplatesDir . 'test.phtml', '<?= $this->escape($this->value) ?>');

        $viewModel = new SearchViewModel(template: 'test', params: ['value' => 'word⟦']);
        $result = $viewModel->render();

        $this->assertStringContainsString('<mark>', $result);
        $this->assertStringContainsString('</mark>', $result);
        $this->assertStringNotContainsString('⟦', $result);
        $this->assertStringContainsString('word', $result);
    }

    public function testEscape_ShouldBalanceUnbalancedClosingMarkers(): void
    {
        file_put_contents(self::$testTemplatesDir . 'test.phtml', '<?= $this->escape($this->value) ?>');

        $viewModel = new SearchViewModel(template: 'test', params: ['value' => '⟧word']);
        $result = $viewModel->render();

        $this->assertStringContainsString('<mark>', $result);
        $this->assertStringContainsString('</mark>', $result);
        $this->assertStringNotContainsString('⟧', $result);
        $this->assertStringContainsString('word', $result);
    }

    public function testEscape_ShouldEscapeHTMLBeforeMarkerConversion(): void
    {
        file_put_contents(self::$testTemplatesDir . 'test.phtml', '<?= $this->escape($this->value) ?>');

        $viewModel = new SearchViewModel(template: 'test', params: ['value' => '<div>⟦test⟧</div>']);
        $result = $viewModel->render();

        $this->assertStringContainsString('&lt;div&gt;', $result);
        $this->assertStringContainsString('<mark>test</mark>', $result);
        $this->assertStringNotContainsString('⟦', $result);
        $this->assertStringNotContainsString('⟧', $result);
    }

    public function testEscape_ShouldHandlePartialMarkers(): void
    {
        file_put_contents(self::$testTemplatesDir . 'test.phtml', '<?= $this->escape($this->value) ?>');

        $viewModel1 = new SearchViewModel(template: 'test', params: ['value' => '⟦test']);
        $result1 = $viewModel1->render();
        $this->assertStringNotContainsString('⟦', $result1);

        $viewModel2 = new SearchViewModel(template: 'test', params: ['value' => 'test⟧']);
        $result2 = $viewModel2->render();
        $this->assertStringNotContainsString('⟧', $result2);

        $viewModel3 = new SearchViewModel(template: 'test', params: ['value' => '⟦full⟧']);
        $result3 = $viewModel3->render();
        $this->assertStringContainsString('<mark>', $result3);
        $this->assertStringContainsString('</mark>', $result3);
    }
}
