<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\View\Search;

use PHPUnit\Framework\TestCase;
use S3\Log\Viewer\View\Search\SearchViewModel;

class SearchViewModelTest extends TestCase
{
    public function testHighlightClass_ShouldReturnExpectedClass(): void
    {
        $viewModel = new SearchViewModel(template: 'dummy');

        $this->assertSame('highlight-string', $viewModel->highlightClass(value: 'text'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '123'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '45.67'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: 123));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: 45.67));
        $this->assertSame('highlight-null', $viewModel->highlightClass(value: null));
        $this->assertSame('highlight-boolean', $viewModel->highlightClass(value: true));
        $this->assertSame('highlight-boolean', $viewModel->highlightClass(value: false));
        $this->assertSame('', $viewModel->highlightClass(value: []));
    }

    public function testRenderValue_ShouldReturnExpectedFormat(): void
    {
        $viewModel = new SearchViewModel(template: 'dummy');

        $this->assertSame('Hello', $viewModel->renderValue(value: 'Hello'));
        $this->assertSame("Line1\nLine2", $viewModel->renderValue(value: "Line1\nLine2"));
        $this->assertSame('42', $viewModel->renderValue(value: '42'));
        $this->assertSame('3.14', $viewModel->renderValue(value: '3.14'));
        $this->assertSame('42', $viewModel->renderValue(value: 42));
        $this->assertSame('3.14', $viewModel->renderValue(value: 3.14));
        $this->assertSame('null', $viewModel->renderValue(value: null));
        $this->assertSame('null', $viewModel->renderValue(value: 'null'));
        $this->assertSame('true', $viewModel->renderValue(value: true));
        $this->assertSame('true', $viewModel->renderValue(value: 'true'));
        $this->assertSame('false', $viewModel->renderValue(value: false));
        $this->assertSame('false', $viewModel->renderValue(value: 'false'));
        $this->assertSame('', $viewModel->renderValue(value: []));
    }
}
