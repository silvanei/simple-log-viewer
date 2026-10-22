# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.4.3] - 2026-10-22

### Security
- **Critical:** Patch CVE-2026-109618 (RCE via OOM corruption) and CVE-2026-109619 (RCE via stale global state) — "PHP2Shell", CVSS v4.0 9.2, unauthenticated remote code execution in PHP core. Affects FrankenPHP, PHP-FPM, mod_php, php-cgi and LiteSpeed. Fixed in PHP 8.5.12; there is no complete configuration workaround
- Require `php >= 8.5.12` in `composer.json` so vulnerable patches (8.5.0 – 8.5.11) are rejected at install time
- Upgrade FrankenPHP 1.12.7 → 1.13.2, which carries 5 security fixes of its own (2 rated high: Windows document-root escape `GHSA-868c-7h7m-mmj9` and header spoofing through dot-form header names `GHSA-qcrp-8483-f2f2`)

### Changed
- Update Docker base image to `dunglas/frankenphp:1.13.2-php8.5.12-alpine` (all 3 stages: development, builder, production)
  - PHP 8.5.11 → 8.5.12
  - FrankenPHP 1.12.7 → 1.13.2
  - Caddy 2.11.4 → 2.11.7 (Slowloris protection, 16 KiB default header limit, reverse proxy fixes)
- Update composer dependencies (22 packages, no downgrades, no removals)
  - `phpstan/phpstan` 2.2.15 → 2.3.1
  - `phpunit/phpunit` 13.3.4 → 13.4.3
  - `infection/infection` 0.35.4 → 0.35.8
  - `phpunit/php-code-coverage` 14.3.3 → 14.4.1
  - `symfony/console`, `symfony/filesystem`, `symfony/finder`, `symfony/process`, `symfony/string` 8.1.x → 8.1.9
  - `symfony/polyfill-*` 1.41.0/1.42.0/1.38.2 → 1.43.0
  - `sanmai/pipeline` 7.10 → 7.12, `webmozart/assert` 2.4.1 → 2.5.0
  - `fidry/cpu-core-counter` 1.3.0 → 1.4.1, `justinrainbow/json-schema` 6.12.0 → 6.13.1
  - `sebastian/environment`, `sebastian/git-state`, `sebastian/version`, `phpunit/php-text-template` patch/minor updates
- Document the minimum supported runtime as PHP 8.5.12+ in `README.md`

### Test
- `make check`: 289 tests / 1010 assertions passing, PHPStan (max level) 0 errors, PHPCS PSR-12 clean (48 files)
- `make infection`: 100% mutation code coverage, MSI 95%
- `composer audit`: no security vulnerability advisories
- Production image smoke test: `HTTP 200` on `/`, `X-Powered-By: PHP/8.5.12`, FrankenPHP 1.13.2 / Caddy 2.11.7 — no Caddy config errors (neither `num_threads` nor Mercure JWT is used, so the 1.13 upgrade notes do not apply)

## [1.4.2] - 2026-09-23

### Changed
- Update Docker image dependencies
  - FrankenPHP 1.12.3 → 1.12.7 (all 3 stages: development, builder, production)
  - Composer 2.10.2 → 2.10.3
  - git-cliff 2.13.1 → 2.14.2
- Update composer dev dependencies
  - `infection/infection` 0.34.0 → 0.35.4 (major)
  - `phpstan/phpstan` 2.2.5 → 2.2.15
  - `phpunit/phpunit` 13.2.4 → 13.3.4
  - `squizlabs/php_codesniffer` 4.0.1 → 4.0.4
- Remove user mapping from Makefile for rootless Docker
- Add `.zed` directory to `.gitignore`

### Security
- Fix CVE-2026-67434 (OS command injection) in `squizlabs/php_codesniffer` — upgrade to `4.0.4`
- `composer audit` now passes with zero vulnerabilities

### Test
- Rename redundant `#[Before]` hooks in `setUp()` template methods to dedicated method names (fixes PHPUnit warnings that blocked Infection)

## [1.4.1] - 2026-07-20

### Changed
- Update Docker image dependencies
  - FrankenPHP 1.11.2 → 1.12.3
  - Composer 2.9.2 → 2.10.2 (includes security fixes)

### Security
- Add multi-stage production build to remove composer binary from final image
- Move `COMPOSER_ALLOW_SUPERUSER=1` to only RUN instructions (not global ENV)
- Update `.dockerignore` with additional security patterns (`.git`, `.env*`, `*.pem`, `*.key`, `*.cert`, `*.log`, `.github`, `*.md`)

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