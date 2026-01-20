<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use S3\Log\Viewer\Controller\HomeAction;

class HomeActionTest extends TestCase
{
    public function testInvoke_ShouldReturnsResponseWith200StatusCode(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvokeSetsCorrectContentTypeHeader(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
    }

    public function testInvokeReturnsCorrectHtmlContent(): void
    {
        $expectedBody = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Log Viewer</title>
            <link rel="icon" type="image/x-icon" href="/favicon.ico">
            <script src="/htmx.min.js"></script>
            <script src="/htmx-ext-sse.js"></script>
            <script src="/hyperscript.min.js"></script>
            <link rel="stylesheet" href="/assets/icons/icons.css">
            <link rel="stylesheet" href="/styles.css">
            <script src="/scripts.js" defer></script>
        </head>
        <body hx-indicator="body">
        <a href="#main-content" class="sr-only focusable">Skip to main content</a>
        <div id="live-status" class="sr-only" aria-live="polite" aria-atomic="true"></div>
        <main id="main-content" class="container">
            <div id="progress-bar"></div>
            <header>
                <h1>Real-time Log Viewer</h1>
                <button id="theme-toggle" class="theme-toggle" aria-label="Theme toggle">
                    <span class="i i-sun sun"></span>
                    <span class="i i-moon moon"></span>
                </button>
            </header>
            <div class="controls">
                <div id="search-container-id" class="search-container" role="search">
            <label class="sr-only" for="search-input">Search for logs</label>
            <label class="search-input-container">
                <input type="search"
                       id="search-input"
                       name="search"
                       placeholder="Search for logs"
                       aria-label="Search for logs"
                       hx-get="/search"
                       hx-trigger="search-trigger"
                       hx-target="#search-results"
                       hx-include=".fields">
                <button id="pause-button" class="pause-button" aria-label="Pause" aria-pressed="false">
                    <span class="i i-pause pause-icon"></span>
                    <span class="i i-play play-icon hidden"></span>
                </button>
                <button id="search-button" class="search-button" aria-label="Search" onclick="triggerSearch()">
                    <span class="i i-search"></span>
                    <span class="notification-dot hidden" aria-hidden="true"></span>
                    <span class="sr-only">New logs available</span>
                </button>
                <button id="clear-logs-button" class="clear-button" aria-label="Clear logs" onclick="clearLogs()">
                    <span class="i i-trash"></span>
                </button>
            </label>
        </div>    </div>

            <div id="search-results" hx-ext="sse" sse-connect="/logs-stream" sse-swap="message"></div>
        </main>
        <div id="keyboard-shortcuts-modal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="keyboard-shortcuts-title" aria-hidden="true">
            <div class="modal-content" role="document">
                <div class="modal-header">
                    <h2 id="keyboard-shortcuts-title">Keyboard Shortcuts</h2>
                    <button class="modal-close" onclick="closeKeyboardShortcutsModal()" aria-label="Close keyboard shortcuts">
                        <span class="i i-close"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <section class="shortcut-section">
                        <h3>Navigation</h3>
                        <table class="shortcuts-table" role="table" aria-label="Keyboard navigation shortcuts">
                            <tbody>
                                <tr role="row">
                                    <td class="shortcut-key"><kbd>Tab</kbd></td>
                                    <td class="shortcut-description">Navigate through interactive elements</td>
                                </tr>
                                <tr role="row">
                                    <td class="shortcut-key"><kbd>↑</kbd> / <kbd>↓</kbd></td>
                                    <td class="shortcut-description">Move between log entries</td>
                                </tr>
                                <tr role="row">
                                    <td class="shortcut-key"><kbd>Home</kbd></td>
                                    <td class="shortcut-description">Jump to first log entry</td>
                                </tr>
                                <tr role="row">
                                    <td class="shortcut-key"><kbd>End</kbd></td>
                                    <td class="shortcut-description">Jump to last log entry</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                    <section class="shortcut-section">
                        <h3>Actions</h3>
                        <table class="shortcuts-table" role="table" aria-label="Keyboard action shortcuts">
                            <tbody>
                                <tr role="row">
                                    <td class="shortcut-key"><kbd>Enter</kbd> / <kbd>Space</kbd></td>
                                    <td class="shortcut-description">Expand or collapse log entry details</td>
                                </tr>
                                <tr role="row">
                                    <td class="shortcut-key"><kbd>Esc</kbd></td>
                                    <td class="shortcut-description">Close this dialog</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                    <section class="shortcut-section">
                        <h3>Help</h3>
                        <table class="shortcuts-table" role="table" aria-label="Help shortcuts">
                            <tbody>
                                <tr role="row">
                                    <td class="shortcut-key"><kbd>?</kbd></td>
                                    <td class="shortcut-description">Show or hide this keyboard shortcuts reference</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                </div>
            </div>
        </div>
        </body>
        </html>
        HTML;

        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $this->assertSame($expectedBody, (string) $response->getBody());
    }
}
