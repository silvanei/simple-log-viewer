# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
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

### Technical Details
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