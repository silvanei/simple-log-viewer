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
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '123'));
        $this->assertSame('highlight-number', $viewModel->highlightClass(value: '45.67'));
        $this->assertSame('highlight-null', $viewModel->highlightClass(value: 'null'));
        $this->assertSame('highlight-boolean', $viewModel->highlightClass(value: 'true'));
        $this->assertSame('highlight-boolean', $viewModel->highlightClass(value: 'false'));
        $this->assertSame('', $viewModel->highlightClass(value: []));
    }
}
