<?php

declare(strict_types=1);

namespace Test\S3\Log\Viewer\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use S3\Log\Viewer\Controller\HomeAction;

class HomeActionTest extends TestCase
{
    public function testInvokeReturnsResponseWith200StatusSode(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testInvokeSetsCorrectContentTypeHeader(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $this->assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
    }

    public function testInvokeReturnsCorrectHtmlContent(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $homeAction = new HomeAction();
        $response = $homeAction->__invoke($request);

        $reflector = new ReflectionClass(HomeAction::class);
        $expectedFilePath = dirname($reflector->getFileName() ?: '') . '/../../public/index.html';

        $this->assertFileExists(
            $expectedFilePath,
            'Index.html file not found on the expected path. Check the project structure.'
        );

        $expectedContent = file_get_contents($expectedFilePath) ?: '';
        $this->assertSame($expectedContent, (string) $response->getBody());
    }
}
