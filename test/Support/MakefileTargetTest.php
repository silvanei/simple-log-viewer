<?php

declare(strict_types=1);

namespace Test\Support\S3\Log\Viewer;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

/**
 * Test that the Makefile contains the expected build and changelog targets.
 */
final class MakefileTargetTest extends TestCase
{
    private string $makefileContent = '';

    #[Before]
    protected function setUp(): void
    {
        parent::setUp();
        $makefilePath = __DIR__ . '/../../Makefile';
        $content = file_get_contents($makefilePath);

        if ($content === false) {
            throw new \RuntimeException('Could not read Makefile');
        }

        $this->makefileContent = $content;
    }

    public function testMakefile_ShouldContainBuildProductionTarget(): void
    {
        $this->assertStringContainsString('build-production:', $this->makefileContent);
    }

    public function testBuildProduction_ShouldUseDockerBuildTarget(): void
    {
        $this->assertStringContainsString('--target production', $this->makefileContent);
    }

    public function testBuildProduction_ShouldTagWithVersion(): void
    {
        $this->assertStringContainsString('$(VERSION)', $this->makefileContent);
    }

    public function testBuildProduction_ShouldTagWithLatest(): void
    {
        $this->assertStringContainsString('latest', $this->makefileContent);
    }

    public function testMakefile_ShouldContainChangelogTarget(): void
    {
        $this->assertStringContainsString('changelog:', $this->makefileContent);
    }

    public function testChangelog_ShouldUseGitCliff(): void
    {
        $this->assertStringContainsString('git-cliff', $this->makefileContent);
    }

    public function testChangelog_ShouldPassVersionArgument(): void
    {
        $this->assertStringContainsString('$(VERSION)', $this->makefileContent);
    }
}
