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

    public function testInvokeReturnsHtmlWithMercureScript(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $body = (string) $response->getBody();

        // Verify HTMX SSE attributes are present
        $this->assertStringContainsString('hx-ext="sse"', $body);
        $this->assertStringContainsString('sse-connect="/.well-known/mercure?match=logs"', $body);
        $this->assertStringContainsString('sse-swap="message"', $body);
    }

    /**
     * Mercure 1.0 (shipped by FrankenPHP 1.13+) rejects the legacy `topic`
     * query parameter with a 400 unless the hub is built with the
     * `deprecated_topic` compatibility tag, which FrankenPHP does not set.
     * The subscribe parameter must be `match`.
     */
    public function testInvokeSseEndpoint_ShouldNotUseLegacyTopicParameter(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $response = (new HomeAction())->__invoke($request);

        $body = (string) $response->getBody();

        $this->assertStringNotContainsString('?topic=', $body);
        $this->assertStringNotContainsString('&topic=', $body);
    }

    public function testInvokeSseEndpoint_ShouldUseTopicMatcherParameter(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $response = (new HomeAction())->__invoke($request);

        $body = (string) $response->getBody();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($body);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $nodes = $xpath->query('//*[@sse-connect]');

        $this->assertNotFalse($nodes);
        $this->assertGreaterThanOrEqual(1, $nodes->length, 'Expected an element declaring sse-connect');

        foreach ($nodes as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $connect = $node->getAttribute('sse-connect');
            $query = parse_url($connect, PHP_URL_QUERY);

            $this->assertIsString($query);
            parse_str($query, $params);

            $this->assertArrayHasKey(
                'match',
                $params,
                'Mercure 1.0 requires the "match" topic matcher parameter'
            );
            $this->assertArrayNotHasKey(
                'topic',
                $params,
                'The legacy "topic" parameter returns 400 on Mercure 1.0'
            );
        }
    }
}
