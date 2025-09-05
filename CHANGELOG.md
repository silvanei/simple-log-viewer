# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **BREAKING CHANGE**: Updated datetime field validation in API logs endpoint to require RFC 3339 Extended format with microseconds
  - Previous format: `2025-05-04T12:00:00+00:00` (RFC 3339 basic)
  - New format: `2025-05-04T12:00:00.000+00:00` (RFC 3339 Extended with microseconds)
  - The microseconds portion is now **required** even if it's zero
  - Updated API documentation with detailed format requirements and examples
  - Updated all tests to use the new format
  - Added comprehensive test coverage for datetime validation edge cases

### Added
- Detailed datetime format validation examples in API documentation
- Code examples for generating valid datetime strings in PHP, JavaScript, Python, and bash
- Comprehensive test cases for datetime format validation
- Documentation of supported and unsupported datetime formats

### Technical Details
- Changed validation from `v::dateTime()` to `v::dateTime(DateTimeInterface::RFC3339_EXTENDED)`
- Supported microsecond formats: `.000`, `.123` (3 digits maximum)
- Unsupported formats: 6-digit microseconds, Z notation for UTC
- All timezone offsets are supported: `+00:00`, `-03:00`, `+05:30`, etc.

### Migration Guide
If you're currently sending logs to the API, you need to update your datetime format:

**Before:**
```json
{
  "datetime": "2025-05-04T12:00:00+00:00"
}
```

**After:**
```json
{
  "datetime": "2025-05-04T12:00:00.000+00:00"
}
```

See the [API Documentation](docs/API.md#generating-valid-datetime-strings) for language-specific examples.