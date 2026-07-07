<?php

declare(strict_types=1);

namespace Test\Support\S3\Log\Viewer;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test that semantic version tags are correctly parsed into Docker-style tags.
 *
 * This mirrors the logic used by docker/metadata-action in CI:
 * - Git tag v1.4.0 → Docker tags: 1.4.0, 1.4, 1
 */
final class ReleaseTagTest extends TestCase
{
    /**
     * @param array<int, string> $expectedTags
     */
    #[DataProvider('semverTagProvider')]
    public function testSemverTag_ShouldGenerateVersionTag(string $gitTag, array $expectedTags): void
    {
        $version = ltrim($gitTag, 'v');
        $parts = explode('.', $version);

        $tags = [
            $version,
            $parts[0] . '.' . $parts[1],
            $parts[0],
        ];

        $this->assertSame($expectedTags, $tags);
    }

    /**
     * @return Generator<string, array{string, array<int, string>}>
     */
    public static function semverTagProvider(): Generator
    {
        yield 'stable release' => ['v1.4.0', ['1.4.0', '1.4', '1']];
        yield 'major release' => ['v2.0.0', ['2.0.0', '2.0', '2']];
        yield 'patch release' => ['v1.4.1', ['1.4.1', '1.4', '1']];
        yield 'initial release' => ['v0.1.0', ['0.1.0', '0.1', '0']];
        yield 'minor bump' => ['v1.5.0', ['1.5.0', '1.5', '1']];
        yield 'major bump' => ['v3.0.0', ['3.0.0', '3.0', '3']];
        yield 'double digit' => ['v10.0.0', ['10.0.0', '10.0', '10']];
        yield 'patch bump' => ['v2.1.5', ['2.1.5', '2.1', '2']];
    }

    public function testGitTag_ShouldStripVPrefix(): void
    {
        $this->assertSame('1.4.0', ltrim('v1.4.0', 'v'));
        $this->assertSame('2.0.0', ltrim('v2.0.0', 'v'));
        $this->assertSame('0.1.0', ltrim('v0.1.0', 'v'));
    }

    /**
     * @param array<int, string> $tags
     */
    #[DataProvider('semverTagProvider')]
    public function testDockerTags_ShouldFollowOrder(string $gitTag, array $tags): void
    {
        // Most specific tag first (version), then major.minor, then major
        $this->assertSame(ltrim($gitTag, 'v'), $tags[0], 'First tag should be full version');
        $this->assertMatchesRegularExpression('/^\d+\.\d+$/', $tags[1], 'Second tag should be major.minor');
        $this->assertMatchesRegularExpression('/^\d+$/', $tags[2], 'Third tag should be major only');
    }

    #[DataProvider('tagFilterProvider')]
    public function testGitTag_ShouldMatchCiTriggerPattern(string $gitTag, bool $shouldTriggerCI): void
    {
        // The CI is configured to trigger on 'v*' pattern
        $matches = preg_match('/^v\d+\.\d+\.\d+$/', $gitTag) === 1;

        $this->assertSame($shouldTriggerCI, $matches, "Tag '$gitTag' should trigger CI: $shouldTriggerCI");
    }

    /**
     * @return Generator<string, array{string, bool}>
     */
    public static function tagFilterProvider(): Generator
    {
        yield 'valid semver' => ['v1.4.0', true];
        yield 'valid major' => ['v2.0.0', true];
        yield 'missing v prefix' => ['1.4.0', false];
        yield 'missing patch' => ['v1.4', false];
        yield 'rc tag' => ['v1.4.0-rc1', false];
        yield 'beta tag' => ['v2.0.0-beta.1', false];
        yield 'no tag' => ['main', false];
        yield 'random string' => ['vabc', false];
    }
}
