# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.4.0] - 2026-07-15

### Changed
- Update all direct dependencies to latest versions
  - `nikic/fast-route` 1.3.0 → 1.3.1 (patch)
  - `phpstan/phpstan` 2.1.39 → 2.2.5 (minor)
  - `infection/infection` 0.31.9 → 0.34.0 (major)
  - `phpunit/phpunit` 12.5.12 → 13.2.4 (major)
  - `respect/validation` 2.4.12 → 3.1.2 (major)
- Migrate Respect Validation to v3 (ValidatorBuilder, isValid(), length() syntax)

### Documentation
- Update AGENTS.md with correct commands and remove Composer references

## [1.3.3] - 2026-07-15

### Added
- Add Makefile targets for test-coverage, phpcbf, audit, and outdated
  - `make test-coverage` - Run tests with HTML coverage report
  - `make phpcbf` - Run PHP Code Beautifier and Fixer
  - `make audit` - Run Composer security audit
  - `make outdated` - Check for outdated Composer dependencies

### Fixed
- Correct typos across CSS, PHP, templates, and docs
  - `wraper` → `wrapper` (CSS class name, 12 occurrences)
  - `renderAdidionalKey` → `renderAdditionalKey` (method name, 14 occurrences)
  - `renderAdidionalField` → `renderAdditionalField` (method name, 2 occurrences)
  - `$aditionalKey` → `$additionalKey` (parameter name, 2 occurrences)
  - `$flattenWithDotsentry` → `$flattenWithDotsEntry` (variable name, 8 occurrences)
  - `;;` → `;` (double semicolon in CSS, 1 occurrence)

## [1.3.2] - 2026-07-07

### Added
- GitHub Release is now automatically created when a git tag is pushed
  - Uses `gh release create` (GitHub CLI) — no third-party actions required
  - Release notes are extracted from `CHANGELOG.md` matching the version
  - Only triggers on tags (`v*.*.*`), not on branch pushes

## [1.3.1] - 2026-07-07

### CI/CD
- Added Docker semantic tags and conventional commits support
  - CI generates Docker images with semantic tags on git tag pushes (`v*.*.*`)
  - Adopted [Conventional Commits](https://www.conventionalcommits.org/) for all commits
  - Added `cliff.toml` for automated changelog generation with [git-cliff](https://git-cliff.org)
  - Created comprehensive `RELEASE.md` with step-by-step release guide
  - Added `changelog` and `build-production` targets to Makefile

### Fixed
- Installed git-cliff v2.13.1 binary in Docker development image for reliable changelog generation
  - Previously depended on external `orhunp/git-cliff` Docker image which failed with libgit2 errors
  - Updated Makefile `changelog` target to run git-cliff via the project's own Docker container
  - Updated RELEASE.md documentation for the new Docker-based workflow

## [1.3.0] - 2026-02-17

### Changed
- Migrated from ReactPHP to FrankenPHP as the HTTP server
  - Replaced ReactPHP with FrankenPHP (PHP 8.5) for improved performance
  - Switched from custom SSE implementation to Mercure Hub for real-time updates
  - Updated all HTTP response types from React\Http\Message to PSR-7 (Nyholm)
  - Removed GzipMiddleware (now handled by Caddy)
  - Removed StaticFileMiddleware (now handled by Caddy)
  - Updated Application.php to implement PSR-15 RequestHandlerInterface

### Technical Details
- Server: FrankenPHP 1.11.2 with Caddy web server
- HTTP Standard: PSR-7 (Nyholm\Psr7) and PSR-15
- Real-time: Mercure Hub (built into FrankenPHP/Caddy)
- SSE Endpoint: `/.well-known/mercure?topic=logs`
- Worker mode enabled for persistent memory and better performance
- Added `enable_full_duplex` configuration in Caddy for SSE support

### Dependencies Updated
- Removed: `react/http`, `clue/sse-react`
- Added: `nyholm/psr7`, `nyholm/psr7-server`, `psr/http-message`, `psr/http-server-handler`, `psr/http-server-middleware`

### Enhanced
- Enhanced datetime field validation in API logs endpoint to support both ISO 8601 and RFC 3339 Extended formats
  - **Backward Compatible**: Still accepts ISO 8601 format: `2025-05-04T12:00:00+00:00` (original format)
  - **New Support**: Also accepts RFC 3339 Extended format: `2025-05-04T12:00:00.000+00:00` (with microseconds)
  - No breaking changes - existing integrations continue to work without modification
  - Updated API documentation with detailed format requirements and examples for both formats
  - Added comprehensive test coverage for both datetime validation formats
  - Enhanced validation logic to accept either format automatically

### Added
- Detailed datetime format validation examples in API documentation
- Code examples for generating valid datetime strings in PHP, JavaScript, Python, and bash
- Comprehensive test cases for datetime format validation
- Documentation of supported and unsupported datetime formats

### Technical Details (Datetime)
- Enhanced validation to support both `v::dateTime('Y-m-d\TH:i:sP')` (ISO 8601) and `v::dateTime(DateTimeInterface::RFC3339_EXTENDED)` formats
- **ISO 8601 format**: `2025-05-04T12:00:00+00:00` (backward compatible)
- **RFC 3339 Extended format**: `2025-05-04T12:00:00.000+00:00` (new option)
- Supported microsecond formats for RFC 3339 Extended: `.000`, `.123` (3 digits maximum)
- Unsupported formats: 6-digit microseconds, Z notation for UTC
- All timezone offsets are supported: `+00:00`, `-03:00`, `+05:30`, etc.

### Usage Examples
Both formats are now supported - no migration required:

**ISO 8601 format (continues to work):**
```json
{
  "datetime": "2025-05-04T12:00:00+00:00"
}
```

**RFC 3339 Extended format (new option):**
```json
{
  "datetime": "2025-05-04T12:00:00.000+00:00"
}
```

See the [API Documentation](docs/API.md#datetime-format-support) for detailed format specifications and language-specific examples.

## [1.2.0] - 2025-05-04