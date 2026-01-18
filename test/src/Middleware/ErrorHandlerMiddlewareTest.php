<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Middleware;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Message\Response;
use RuntimeException;
use S3\Log\Viewer\Middleware\ErrorHandlerMiddleware;
use InvalidArgumentException;
use Error;

#[AllowMockObjectsWithoutExpectations]
final class ErrorHandlerMiddlewareTest extends TestCase
{
    private ErrorHandlerMiddleware $middleware;
    private ServerRequestInterface&MockObject $request;
    private UriInterface&MockObject $uri;

    private const string EXPECTED_JSON_ERROR = <<<'JSON'
    {
        "error": "Internal server error"
    }

    JSON;

    private const string EXPECTED_HTML_ERROR = <<<'HTML'
    <h1>An error occurred</h1>
    HTML;

    #[Before]
    protected function init(): void
    {
        $this->middleware = new ErrorHandlerMiddleware();
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->uri = $this->createMock(UriInterface::class);
        $this->request->method('getUri')->willReturn($this->uri);
    }

    public function testCatchesExceptionFromNextMiddleware(): void
    {
        $this->uri->method('getPath')->willReturn('/some/path');
        $next = fn () => throw new RuntimeException('Database connection failed');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('Database connection failed', $body);
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testReturnsGenericHtmlErrorForUiEndpoints(): void
    {
        $this->uri->method('getPath')->willReturn('/search');
        $next = fn () => throw new RuntimeException('Detailed error message here');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringContainsString('An error occurred', $body);
        $this->assertStringNotContainsString('Detailed error message here', $body);
    }

    public function testReturnsGenericJsonErrorForApiEndpoints(): void
    {
        $this->uri->method('getPath')->willReturn('/api/logs');
        $next = fn () => throw new RuntimeException('Detailed error message here');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Internal server error', $data['error']);
        $this->assertStringNotContainsString('Detailed error message here', $body);
    }

    public function testDoesNotCatchExceptionWhenNextSucceeds(): void
    {
        $this->uri->method('getPath')->willReturn('/some/path');
        $expectedBody = 'Success response';
        $next = fn () => new Response(200, [], $expectedBody);

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedBody, (string) $response->getBody());
    }

    public function testHandlesApiPathCaseInsensitively(): void
    {
        $this->uri->method('getPath')->willReturn('/API/logs');
        $next = fn () => throw new RuntimeException('Some error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('Some error', $body);
    }

    public function testHandlesApiPathWithQueryString(): void
    {
        $this->uri->method('getPath')->willReturn('/api/search');
        $this->uri->method('__toString')->willReturn('/api/search?q=test');
        $next = fn () => throw new RuntimeException('Some error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testHandlesExceptionWithLongMessage(): void
    {
        $longMessage = str_repeat('This is a very long error message. ', 100);
        $this->uri->method('getPath')->willReturn('/search');
        $this->uri->method('__toString')->willReturn('/search');
        $next = fn () => throw new RuntimeException($longMessage);

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('very long error message', $body);
        $this->assertStringNotContainsString(substr($longMessage, 0, 50), $body);
    }

    public function testHandlesExceptionWithSpecialCharacters(): void
    {
        $specialMessage = '<script>alert("xss")</script>\\n"quotes" and \'apostrophes\'';
        $this->uri->method('getPath')->willReturn('/search');
        $this->uri->method('__toString')->willReturn('/search');
        $next = fn () => throw new RuntimeException($specialMessage);

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('<script>', $body);
        $this->assertStringNotContainsString('alert', $body);
        $this->assertStringNotContainsString('quotes', $body);
        $this->assertStringNotContainsString('apostrophes', $body);
    }

    public static function exceptionTypeProvider(): \Generator
    {
        yield 'RuntimeException' => [new RuntimeException('Runtime error')];
        yield 'InvalidArgumentException' => [new InvalidArgumentException('Invalid argument')];
        yield 'Exception' => [new \Exception('Generic exception')];
    }

    #[DataProvider('exceptionTypeProvider')]
    public function testHandlesDifferentExceptionTypes(\Throwable $exception): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $this->uri->method('__toString')->willReturn('/test');
        $next = fn () => throw $exception;

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString($exception->getMessage(), $body);
    }

    public function testNeverExposesStackTrace(): void
    {
        $traceMessage = 'Error at /home/user/project/src/Controller.php:45: Database failed';
        $this->uri->method('getPath')->willReturn('/search');
        $this->uri->method('__toString')->willReturn('/search');
        $next = fn () => throw new RuntimeException($traceMessage);

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('/home/user/project/', $body);
        $this->assertStringNotContainsString('.php', $body);
        $this->assertStringNotContainsString('Controller.php:45', $body);
        $this->assertStringNotContainsString('Database failed', $body);
    }

    public function testNeverExposesSensitiveData(): void
    {
        $sensitiveMessage = 'password=secret123&token=abc456def&apiKey=xyz789';
        $this->uri->method('getPath')->willReturn('/search');
        $this->uri->method('__toString')->willReturn('/search');
        $next = fn () => throw new RuntimeException($sensitiveMessage);

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString('secret', $body);
        $this->assertStringNotContainsString('token', $body);
        $this->assertStringNotContainsString('abc456def', $body);
        $this->assertStringNotContainsString('apiKey', $body);
        $this->assertStringNotContainsString('xyz789', $body);
    }

    public function testHandlesNonApiRootPath(): void
    {
        $this->uri->method('getPath')->willReturn('/');
        $next = fn () => throw new RuntimeException('Root error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('Root error', $body);
    }

    public function testHandlesApiPathWithoutTrailingSlash(): void
    {
        $this->uri->method('getPath')->willReturn('/api');
        $next = fn () => throw new RuntimeException('API root error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('API root error', $body);
    }

    public function testHandlesEmptyPath(): void
    {
        $this->uri->method('getPath')->willReturn('');
        $next = fn () => throw new RuntimeException('Empty path error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('Empty path error', $body);
    }

    public function testHandlesErrorType(): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $this->uri->method('__toString')->willReturn('/test');
        $next = fn () => throw new Error('Fatal error occurred');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('Fatal error occurred', $body);
    }

    public function testHandlesApiSubdirectory(): void
    {
        $this->uri->method('getPath')->willReturn('/api/v1/logs');
        $this->uri->method('__toString')->willReturn('/api/v1/logs');
        $next = fn () => throw new RuntimeException('Subdirectory error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('Subdirectory error', $body);
    }

    public function testReturnsExactJsonErrorBody(): void
    {
        $this->uri->method('getPath')->willReturn('/api/test');
        $next = fn () => throw new RuntimeException('Some error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_JSON_ERROR, (string) $response->getBody());
    }

    public function testReturnsExactHtmlErrorBody(): void
    {
        $this->uri->method('getPath')->willReturn('/test');
        $next = fn () => throw new RuntimeException('Some error');

        $response = ($this->middleware)($this->request, $next);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(self::EXPECTED_HTML_ERROR, (string) $response->getBody());
    }
}
