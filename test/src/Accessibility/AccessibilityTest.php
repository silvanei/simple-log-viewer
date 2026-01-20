<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Accessibility;

use Generator;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use S3\Log\Viewer\Controller\HomeAction;
use S3\Log\Viewer\Controller\SearchAction;
use S3\Log\Viewer\Dto\LogEntryView;
use S3\Log\Viewer\LogService;

#[AllowMockObjectsWithoutExpectations]
class AccessibilityTest extends TestCase
{
    private string $homeHtml;
    private string $searchHtml;
    private \DOMDocument $homeDom;
    private \DOMDocument $searchDom;

    #[Before]
    protected function setUp(): void
    {
        parent::setUp();

        // Get home page HTML
        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction($request);
        $this->homeHtml = (string) $response->getBody();

        // Parse home page HTML
        libxml_use_internal_errors(true);
        $this->homeDom = new \DOMDocument();
        $this->homeDom->loadHTML($this->homeHtml);
        libxml_clear_errors();

        // Get search results HTML
        $logService = $this->createMock(LogService::class);
        $logService->method('search')->willReturn([
            new LogEntryView(
                datetime: '2025-04-28T10:00:00Z',
                channel: 'app',
                level: 'ERROR',
                message: 'Test error message',
                context: ['foo' => 'bar']
            ),
        ]);

        $searchRequest = $this->createMock(ServerRequestInterface::class);
        $searchRequest->method('getQueryParams')->willReturn(['search' => 'test']);
        $searchAction = new SearchAction($logService);
        $searchResponse = $searchAction($searchRequest);
        $this->searchHtml = (string) $searchResponse->getBody();

        // Parse search results HTML
        $this->searchDom = new \DOMDocument();
        $this->searchDom->loadHTML($this->searchHtml);
        libxml_clear_errors();
    }

    /**
     * Helper method to get DOMNodeList from XPath query
     *
     * @param \DOMNodeList<\DOMNode|\DOMNameSpaceNode>|false $nodeList
     * @return \DOMNodeList<\DOMNode|\DOMNameSpaceNode>
     */
    private function getNodeList(\DOMNodeList|false $nodeList): \DOMNodeList
    {
        $this->assertNotFalse($nodeList, 'XPath query should return a node list');

        return $nodeList;
    }

    // ==========================================================================
    // LANDMARK STRUCTURE TESTS
    // ==========================================================================

    public function testMainLandmark_ShouldExist(): void
    {
        $mainElements = $this->homeDom->getElementsByTagName('main');
        $this->assertCount(1, $mainElements, 'There should be exactly one main landmark');

        /** @var \DOMElement $main */
        $main = $mainElements->item(0);
        $this->assertSame('main-content', $main->getAttribute('id'));
    }

    public function testSkipLink_ShouldExistAndHaveCorrectAttributes(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $skipLinks = $this->getNodeList($xpath->query('//a[contains(@class, "sr-only")]'));

        $this->assertCount(1, $skipLinks, 'Skip link should exist');

        /** @var \DOMElement $skipLink */
        $skipLink = $skipLinks->item(0);
        $this->assertSame('#main-content', $skipLink->getAttribute('href'));
        $this->assertStringContainsString('focusable', $skipLink->getAttribute('class'));
        $this->assertStringContainsString('Skip to main content', $skipLink->textContent);
    }

    public function testSkipLink_ShouldHaveFocusableClass(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $skipLinks = $this->getNodeList($xpath->query('//a[contains(@class, "focusable")]'));
        $this->assertCount(1, $skipLinks, 'Skip link should have focusable class');
    }

    public function testSearchContainer_ShouldHaveSearchRole(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $searchContainers = $this->getNodeList($xpath->query('//*[@id="search-container-id"]'));

        $this->assertCount(1, $searchContainers, 'Search container should exist');

        /** @var \DOMElement $searchContainer */
        $searchContainer = $searchContainers->item(0);
        $this->assertSame('search', $searchContainer->getAttribute('role'));
    }

    #[DataProvider('headingProvider')]
    public function testHeadingHierarchy_ShouldBeCorrect(string $tag, int $expectedCount): void
    {
        $headings = $this->homeDom->getElementsByTagName($tag);
        $this->assertGreaterThanOrEqual($expectedCount, $headings->length, "Expected at least {$expectedCount} <{$tag}> elements");
    }

    /**
     * @return Generator<string, array{string, int}>
     */
    public static function headingProvider(): Generator
    {
        yield 'h1' => ['h1', 1];
        yield 'h2' => ['h2', 1];
        yield 'h3' => ['h3', 3];
    }

    public function testHeadingOrder_ShouldBeLogical(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $headings = $xpath->query('//h1 | //h2 | //h3');

        $this->assertGreaterThan(0, $headings->length, 'Page should have headings');

        /** @var \DOMElement $firstHeading */
        $firstHeading = $headings->item(0);
        $this->assertSame('h1', $firstHeading->nodeName, 'First heading should be h1');
    }

    // ==========================================================================
    // ARIA LIVE REGIONS TESTS
    // ==========================================================================

    public function testLiveStatusRegion_ShouldExist(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $liveRegions = $xpath->query('//*[@id="live-status"]');

        $this->assertCount(1, $liveRegions, 'Live status region should exist');

        /** @var \DOMElement $liveRegion */
        $liveRegion = $liveRegions->item(0);
        $this->assertSame('polite', $liveRegion->getAttribute('aria-live'));
        $this->assertSame('true', $liveRegion->getAttribute('aria-atomic'));
    }

    public function testLiveStatusRegion_ShouldBeScreenReaderOnly(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $liveRegions = $xpath->query('//*[@id="live-status"]');

        /** @var \DOMElement $liveRegion */
        $liveRegion = $liveRegions->item(0);
        $this->assertStringContainsString('sr-only', $liveRegion->getAttribute('class'));
    }

    public function testSearchResultsLiveRegion_ShouldExist(): void
    {
        $this->assertStringContainsString('aria-live="polite"', $this->searchHtml);
        $this->assertStringContainsString('aria-atomic="true"', $this->searchHtml);
    }

    public function testNoResultsMessage_ShouldHavePoliteLive(): void
    {
        $this->assertStringContainsString('aria-live="polite"', $this->searchHtml);
    }

    public function testPauseButton_ShouldHaveAriaPressed(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $pauseButtons = $xpath->query('//*[@id="pause-button"]');

        $this->assertCount(1, $pauseButtons, 'Pause button should exist');

        /** @var \DOMElement $pauseButton */
        $pauseButton = $pauseButtons->item(0);
        $this->assertSame('false', $pauseButton->getAttribute('aria-pressed'));
        $this->assertSame('Pause', $pauseButton->getAttribute('aria-label'));
    }

    public function testExpandButtons_ShouldHaveAriaExpandedAndControls(): void
    {
        $xpath = new \DOMXPath($this->searchDom);
        $expandButtons = $xpath->query('//button[contains(@aria-label, "Expand")]');

        $this->assertGreaterThan(0, $expandButtons->length, 'Expand buttons should exist');

        /** @var \DOMElement $expandButton */
        $expandButton = $expandButtons->item(0);
        $this->assertNotEmpty($expandButton->getAttribute('aria-controls'));
        $this->assertNotEmpty($expandButton->getAttribute('aria-expanded'));
    }

    // ==========================================================================
    // KEYBOARD NAVIGATION TESTS
    // ==========================================================================

    public function testAllInteractiveElements_ShouldBeFocusable(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $interactiveElements = $xpath->query('//button | //a | //input');

        $this->assertGreaterThan(0, $interactiveElements->length, 'Should have interactive elements');

        $foundFocusableElement = false;
        foreach ($interactiveElements as $element) {
            if ($element instanceof \DOMElement) {
                $tagName = $element->tagName;

                // Buttons and anchors are naturally focusable
                // Input elements with type="search" are naturally focusable
                if (in_array($tagName, ['button', 'a', 'input'], true)) {
                    $foundFocusableElement = true;
                    break;
                }
            }
        }

        $this->assertTrue($foundFocusableElement, 'Should have at least one focusable interactive element');
    }

    public function testLogEntryRows_ShouldHaveTabindexZero(): void
    {
        $xpath = new \DOMXPath($this->searchDom);
        $logRows = $xpath->query('//div[contains(@class, "row-main")]');

        $this->assertGreaterThan(0, $logRows->length, 'Should have log entry rows');

        /** @var \DOMElement $row */
        foreach ($logRows as $row) {
            $this->assertSame('0', $row->getAttribute('tabindex'), 'Log row should have tabindex="0"');
        }
    }

    public function testModal_ShouldHaveProperAriaAttributes(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $modals = $xpath->query('//*[@role="dialog"]');

        $this->assertCount(1, $modals, 'Modal should exist');

        /** @var \DOMElement $modal */
        $modal = $modals->item(0);
        $this->assertSame('true', $modal->getAttribute('aria-modal'));
        $this->assertNotEmpty($modal->getAttribute('aria-labelledby'));
        $this->assertSame('true', $modal->getAttribute('aria-hidden'));
    }

    public function testModalCloseButton_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $closeButtons = $xpath->query('//button[contains(@class, "modal-close")]');

        $this->assertCount(1, $closeButtons, 'Modal close button should exist');

        /** @var \DOMElement $closeButton */
        $closeButton = $closeButtons->item(0);
        $this->assertStringContainsString('Close', $closeButton->getAttribute('aria-label'));
    }

    // ==========================================================================
    // SCREEN READER SUPPORT TESTS
    // ==========================================================================

    public function testIconButtons_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $buttons = $xpath->query('//button[.//span[contains(@class, "i-")]]');

        $this->assertGreaterThan(0, $buttons->length, 'Should have icon buttons');

        /** @var \DOMElement $button */
        foreach ($buttons as $button) {
            $ariaLabel = $button->getAttribute('aria-label');
            $srOnlyText = $button->textContent;

            $hasAccessibleLabel = ! empty($ariaLabel) || str_contains($srOnlyText, 'Toggle') || str_contains($srOnlyText, 'Remove');

            $this->assertTrue(
                $hasAccessibleLabel,
                'Icon button should have aria-label or screen reader text'
            );
        }
    }

    public function testSearchInput_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $searchInput = $xpath->query('//*[@id="search-input"]');

        $this->assertCount(1, $searchInput, 'Search input should exist');

        /** @var \DOMElement $input */
        $input = $searchInput->item(0);
        $this->assertSame('Search for logs', $input->getAttribute('aria-label'));
    }

    public function testLogEntryLevelIcons_ShouldHaveAriaHidden(): void
    {
        $xpath = new \DOMXPath($this->searchDom);
        $levelIcons = $xpath->query('//div[contains(@class, "level")]//span[contains(@class, "i-")]');

        $this->assertGreaterThan(0, $levelIcons->length, 'Should have level icons');

        /** @var \DOMElement $icon */
        $icon = $levelIcons->item(0);
        $this->assertSame('true', $icon->getAttribute('aria-hidden'));
    }

    public function testLogEntries_ShouldHaveAccessibleName(): void
    {
        $xpath = new \DOMXPath($this->searchDom);
        $logRows = $xpath->query('//div[contains(@class, "row-main")]');

        $this->assertGreaterThan(0, $logRows->length, 'Should have log entries');

        /** @var \DOMElement $row */
        $row = $logRows->item(0);
        $ariaLabel = $row->getAttribute('aria-label');

        $this->assertNotEmpty($ariaLabel, 'Log entry should have aria-label');
        $this->assertStringContainsString('Log entry:', $ariaLabel);
    }

    public function testTableHeaders_ShouldHaveScope(): void
    {
        $xpath = new \DOMXPath($this->searchDom);
        $headers = $xpath->query('//div[@role="columnheader"]');

        $this->assertGreaterThan(0, $headers->length, 'Should have table headers');

        /** @var \DOMElement $header */
        foreach ($headers as $header) {
            $this->assertSame('col', $header->getAttribute('scope'));
        }
    }

    public function testTable_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->searchDom);
        $tables = $xpath->query('//div[@role="table"]');

        $this->assertCount(1, $tables, 'Table should exist');

        /** @var \DOMElement $table */
        $table = $tables->item(0);
        $this->assertNotEmpty($table->getAttribute('aria-label'));
    }

    public function testSearchResultsCount_ShouldBeAnnouncedToScreenReaders(): void
    {
        $this->assertStringContainsString('Showing 1 log entries', $this->searchHtml);
        $this->assertStringContainsString('aria-live="polite"', $this->searchHtml);
        $this->assertStringContainsString('aria-atomic="true"', $this->searchHtml);
    }

    // ==========================================================================
    // CSS FILE LINKS TESTS
    // ==========================================================================

    public function testStylesheet_ShouldBeLinked(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $stylesheets = $xpath->query('//link[@rel="stylesheet" and contains(@href, "styles.css")]');

        $this->assertCount(1, $stylesheets, 'Stylesheet should be linked');
    }

    public function testIconsStylesheet_ShouldBeLinked(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $stylesheets = $xpath->query('//link[@rel="stylesheet" and contains(@href, "icons.css")]');

        $this->assertCount(1, $stylesheets, 'Icons stylesheet should be linked');
    }

    // ==========================================================================
    // FORM LABEL ASSOCIATION TESTS
    // ==========================================================================

    public function testSearchInput_ShouldHaveLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $searchInput = $xpath->query('//*[@id="search-input"]');

        /** @var \DOMElement $input */
        $input = $searchInput->item(0);

        // Check for aria-label
        $hasAriaLabel = ! empty($input->getAttribute('aria-label'));
        $this->assertTrue($hasAriaLabel, 'Search input should have aria-label');

        // Check for associated label element
        $xpathLabels = new \DOMXPath($this->homeDom);
        $labels = $xpathLabels->query('//label[@for="search-input"]');
        $this->assertCount(1, $labels, 'Search input should have associated label');
    }

    public function testFormLabels_ShouldBeScreenReaderOnly(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $labels = $xpath->query('//label[@for="search-input"]');

        /** @var \DOMElement $label */
        $label = $labels->item(0);
        $this->assertStringContainsString('sr-only', $label->getAttribute('class'));
    }

    // ==========================================================================
    // BUTTON AND CONTROL TESTS
    // ==========================================================================

    public function testThemeToggle_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $themeToggle = $xpath->query('//*[@id="theme-toggle"]');

        $this->assertCount(1, $themeToggle, 'Theme toggle should exist');

        /** @var \DOMElement $toggle */
        $toggle = $themeToggle->item(0);
        $this->assertSame('Theme toggle', $toggle->getAttribute('aria-label'));
    }

    public function testClearLogsButton_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $clearButton = $xpath->query('//*[@id="clear-logs-button"]');

        $this->assertCount(1, $clearButton, 'Clear logs button should exist');

        /** @var \DOMElement $button */
        $button = $clearButton->item(0);
        $this->assertSame('Clear logs', $button->getAttribute('aria-label'));
    }

    public function testSearchButton_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $searchButton = $xpath->query('//*[@id="search-button"]');

        $this->assertCount(1, $searchButton, 'Search button should exist');

        /** @var \DOMElement $button */
        $button = $searchButton->item(0);
        $this->assertSame('Search', $button->getAttribute('aria-label'));
    }

    public function testNotificationDot_ShouldHaveAriaHidden(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $dots = $xpath->query('//span[contains(@class, "notification-dot")]');

        $this->assertCount(1, $dots, 'Notification dot should exist');

        /** @var \DOMElement $dot */
        $dot = $dots->item(0);
        $this->assertSame('true', $dot->getAttribute('aria-hidden'));
    }

    // ==========================================================================
    // KEYBOARD SHORTCUTS MODAL TESTS
    // ==========================================================================

    public function testKeyboardShortcutsModal_ShouldHaveProperStructure(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $modal = $xpath->query('//*[@id="keyboard-shortcuts-modal"]');

        $this->assertCount(1, $modal, 'Keyboard shortcuts modal should exist');

        /** @var \DOMElement $modalElement */
        $modalElement = $modal->item(0);

        $this->assertSame('dialog', $modalElement->getAttribute('role'));
        $this->assertSame('true', $modalElement->getAttribute('aria-modal'));
    }

    public function testKeyboardShortcutsModal_ShouldHaveLabelledBy(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $modal = $xpath->query('//*[@id="keyboard-shortcuts-modal"]');

        /** @var \DOMElement $modalElement */
        $modalElement = $modal->item(0);

        $labelledBy = $modalElement->getAttribute('aria-labelledby');
        $this->assertSame('keyboard-shortcuts-title', $labelledBy);
    }

    public function testKeyboardShortcutsTitle_ShouldExist(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $title = $xpath->query('//*[@id="keyboard-shortcuts-title"]');

        $this->assertCount(1, $title, 'Keyboard shortcuts title should exist');

        /** @var \DOMElement $titleElement */
        $titleElement = $title->item(0);
        $this->assertSame('h2', $titleElement->nodeName);
    }

    public function testShortcutTables_ShouldHaveAriaLabel(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $tables = $xpath->query('//table[contains(@class, "shortcuts-table")]');

        $this->assertGreaterThan(0, $tables->length, 'Shortcut tables should exist');

        /** @var \DOMElement $table */
        foreach ($tables as $table) {
            $this->assertNotEmpty($table->getAttribute('aria-label'));
        }
    }

    // ==========================================================================
    // SCREEN READER ONLY CLASS TESTS
    // ==========================================================================

    public function testMultipleElements_ShouldUseSrOnlyClass(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $srOnlyElements = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " sr-only ")]');

        $this->assertGreaterThanOrEqual(4, $srOnlyElements->length, 'Multiple elements should use sr-only class');
    }

    public function testTitle_ShouldExist(): void
    {
        $titles = $this->homeDom->getElementsByTagName('title');
        $this->assertCount(1, $titles, 'Page should have title');

        /** @var \DOMElement $title */
        $title = $titles->item(0);
        $this->assertNotEmpty($title->textContent);
    }

    public function testMetaCharset_ShouldExist(): void
    {
        $xpath = new \DOMXPath($this->homeDom);
        $metaCharset = $xpath->query('//meta[@charset]');

        $this->assertCount(1, $metaCharset, 'Page should have meta charset');
    }

    // ==========================================================================
    // ERROR HANDLING TESTS
    // ==========================================================================

    public function testSearchAction_WithEmptyResults_ShouldReturnAccessibleHtml(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->method('search')->willReturn([]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['search' => 'nonexistent']);

        $action = new SearchAction($logService);
        $response = $action($request);
        $html = (string) $response->getBody();

        // Should have accessible "no results" message
        $this->assertStringContainsString('No log entries found', $html);
        $this->assertStringContainsString('aria-live', $html);
    }

    public function testSearchAction_WithServiceError_ShouldThrowException(): void
    {
        $this->expectException(RuntimeException::class);

        $logService = $this->createMock(LogService::class);
        $logService->method('search')->willThrowException(new RuntimeException('Database error'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['search' => 'test']);

        $action = new SearchAction($logService);
        $action($request);
    }
}
