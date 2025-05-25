<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\View;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use S3\Log\Viewer\View\GenericViewModel;

class GenericViewModelTest extends TestCase
{
    private static string $templatesRoot;
    private static string $testTemplatesDir;

    #[Before]
    public static function setupTemplateRootTemporaryDirectory(): void
    {
        self::$testTemplatesDir = getenv('APP_ROOT') . '/storage/templates_test_' . uniqid() . '/';
        mkdir(self::$testTemplatesDir);
        self::$templatesRoot = getenv('TEMPLATES_ROOT') ?: '';
        putenv('TEMPLATES_ROOT=' . self::$testTemplatesDir);
    }

    #[After]
    public static function clearTemplateRootTemporaryDirectory(): void
    {
        array_map(unlink(...), glob(self::$testTemplatesDir . '*') ?: []);
        rmdir(self::$testTemplatesDir);
        putenv('TEMPLATES_ROOT=' . self::$templatesRoot);
    }

    public function testRenderWithoutExtends_ShouldRenderExpectedBody(): void
    {
        file_put_contents(self::$testTemplatesDir . 'test.phtml', 'Hello, <?= $this->name ?>');

        $instance = new GenericViewModel(template: 'test', params: ['name' => 'World']);

        $this->assertSame('Hello, World', $instance->render());
    }

    public function testRenderWithExtends_ShouldRenderExpectedBody(): void
    {
        file_put_contents(self::$testTemplatesDir . 'child.phtml', 'Child Content');
        file_put_contents(self::$testTemplatesDir . 'parent.phtml', '<div><?= $this->slot ?></div>');

        $instance = new GenericViewModel('child', [], 'parent');

        $this->assertSame('<div>Child Content</div>', $instance->render());
    }

    public function testRender_WhenCallPartialMethodInsideTemplate_ShouldReturnExpectedBody(): void
    {
        file_put_contents(self::$testTemplatesDir . 'parent.phtml', '<div><?= $this->partial("partial", ["key" => "test"]) ?></div>');
        file_put_contents(self::$testTemplatesDir . 'partial.phtml', 'Partial: <?= $this->key ?>');

        $instance = new GenericViewModel('parent');

        $this->assertSame('<div>Partial: test</div>', $instance->render());
    }

    public function testEscape_ShouldReturnCleanContent(): void
    {
        $input = '<script>alert("XSS")</script>';
        file_put_contents(self::$testTemplatesDir . 'test.phtml', '<?= $this->escape($this->input) ?>');
        $expected = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $instance = new GenericViewModel('test', ['input' => $input]);

        $this->assertSame($expected, $instance->render());
    }

    public function testRender_ShouldThrowException_WhenNotFoundTemplate(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found: ' . self::$testTemplatesDir . 'non_existent.phtml');

        new GenericViewModel('non_existent')->render();
    }
}
